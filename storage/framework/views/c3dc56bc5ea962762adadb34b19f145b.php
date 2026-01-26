<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 py-12 px-4 sm:px-6 lg:px-8 browser-only" x-data="plansPage()">
    <!-- Header -->
    <div class="max-w-7xl mx-auto text-center mb-12">
        <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-4">
            Выберите тарифный план
        </h1>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Все возможности SellerMind AI для вашего бизнеса на маркетплейсах
        </p>

        <!-- Billing Period Toggle -->
        <div class="mt-8 inline-flex items-center bg-white rounded-full p-1 shadow-sm border border-gray-200">
            <button @click="billingPeriod = 'monthly'"
                    :class="billingPeriod === 'monthly' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:text-gray-900'"
                    class="px-6 py-2 rounded-full font-medium transition-all">
                Ежемесячно
            </button>
            <button @click="billingPeriod = 'quarterly'"
                    :class="billingPeriod === 'quarterly' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:text-gray-900'"
                    class="px-6 py-2 rounded-full font-medium transition-all">
                Квартально <span class="text-xs ml-1 opacity-75">(-10%)</span>
            </button>
            <button @click="billingPeriod = 'yearly'"
                    :class="billingPeriod === 'yearly' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:text-gray-900'"
                    class="px-6 py-2 rounded-full font-medium transition-all">
                Годовой <span class="text-xs ml-1 opacity-75">(-20%)</span>
            </button>
        </div>
    </div>

    <!-- Loading State -->
    <template x-if="loading">
        <div class="max-w-7xl mx-auto text-center py-20">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-indigo-500 border-t-transparent"></div>
            <p class="mt-4 text-gray-600">Загрузка тарифов...</p>
        </div>
    </template>

    <!-- Plans Grid -->
    <div x-show="!loading" class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <template x-for="plan in plans" :key="plan.id">
            <div class="relative bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden transition-all hover:scale-105 hover:shadow-2xl"
                 :class="plan.is_popular ? 'ring-4 ring-indigo-500' : ''">

                <!-- Popular Badge -->
                <div x-show="plan.is_popular" class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        Популярный
                    </span>
                </div>

                <div class="p-8">
                    <!-- Plan Name -->
                    <h3 class="text-2xl font-bold text-gray-900 mb-2" x-text="plan.name"></h3>
                    <p class="text-sm text-gray-600 mb-6 min-h-[3rem]" x-text="plan.description"></p>

                    <!-- Price -->
                    <div class="mb-6">
                        <div class="flex items-baseline">
                            <span class="text-4xl font-bold text-gray-900" x-text="formatPrice(calculatePrice(plan))"></span>
                            <span class="text-gray-600 ml-2">/ мес</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1" x-text="getBillingLabel()"></p>
                    </div>

                    <!-- CTA Button -->
                    <button @click="selectPlan(plan)"
                            :class="plan.is_popular ? 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 shadow-lg shadow-indigo-500/25' : 'bg-gray-900 hover:bg-gray-800'"
                            class="w-full py-3 px-6 text-white font-semibold rounded-xl transition-all mb-6">
                        Выбрать план
                    </button>

                    <!-- Features List -->
                    <div class="space-y-3">
                        <div class="flex items-start text-sm text-gray-700">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span><strong x-text="plan.limits.products"></strong> товаров</span>
                        </div>
                        <div class="flex items-start text-sm text-gray-700">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span><strong x-text="plan.limits.orders_per_month"></strong> заказов/мес</span>
                        </div>
                        <div class="flex items-start text-sm text-gray-700">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span><strong x-text="plan.limits.marketplace_accounts"></strong> маркетплейсов</span>
                        </div>
                        <div class="flex items-start text-sm text-gray-700">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span><strong x-text="plan.limits.users"></strong> пользователей</span>
                        </div>
                        <div class="flex items-start text-sm text-gray-700">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span><strong x-text="plan.limits.ai_requests"></strong> AI запросов/мес</span>
                        </div>

                        <!-- Additional Features -->
                        <div class="pt-4 border-t border-gray-200 mt-4 space-y-2">
                            <template x-for="feature in plan.feature_list" :key="feature">
                                <div class="flex items-start text-sm text-gray-600">
                                    <svg class="w-5 h-5 text-indigo-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                    <span x-text="feature"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Features Comparison -->
    <div class="max-w-7xl mx-auto mt-20 bg-white rounded-2xl shadow-xl p-8 border border-gray-200">
        <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Сравнение возможностей</h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Функция</th>
                        <template x-for="plan in plans" :key="plan.id">
                            <th class="text-center py-4 px-4 text-sm font-semibold text-gray-700" x-text="plan.name"></th>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="py-3 px-4 text-sm text-gray-900">API доступ</td>
                        <template x-for="plan in plans" :key="plan.id">
                            <td class="text-center py-3 px-4">
                                <span x-show="plan.features.api_access" class="text-green-500">✓</span>
                                <span x-show="!plan.features.api_access" class="text-gray-300">–</span>
                            </td>
                        </template>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 text-sm text-gray-900">Приоритетная поддержка</td>
                        <template x-for="plan in plans" :key="plan.id">
                            <td class="text-center py-3 px-4">
                                <span x-show="plan.features.priority_support" class="text-green-500">✓</span>
                                <span x-show="!plan.features.priority_support" class="text-gray-300">–</span>
                            </td>
                        </template>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 text-sm text-gray-900">Telegram уведомления</td>
                        <template x-for="plan in plans" :key="plan.id">
                            <td class="text-center py-3 px-4">
                                <span x-show="plan.features.telegram_notifications" class="text-green-500">✓</span>
                                <span x-show="!plan.features.telegram_notifications" class="text-gray-300">–</span>
                            </td>
                        </template>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 text-sm text-gray-900">Автоценообразование</td>
                        <template x-for="plan in plans" :key="plan.id">
                            <td class="text-center py-3 px-4">
                                <span x-show="plan.features.auto_pricing" class="text-green-500">✓</span>
                                <span x-show="!plan.features.auto_pricing" class="text-gray-300">–</span>
                            </td>
                        </template>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 text-sm text-gray-900">Расширенная аналитика</td>
                        <template x-for="plan in plans" :key="plan.id">
                            <td class="text-center py-3 px-4">
                                <span x-show="plan.features.analytics" class="text-green-500">✓</span>
                                <span x-show="!plan.features.analytics" class="text-gray-300">–</span>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="max-w-4xl mx-auto mt-20">
        <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Часто задаваемые вопросы</h2>

        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-2">Можно ли сменить тариф в любое время?</h3>
                <p class="text-gray-600">Да, вы можете повысить или понизить тариф в любой момент. При повышении тарифа будет произведен перерасчет оставшегося периода.</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-2">Есть ли пробный период?</h3>
                <p class="text-gray-600">Да, все новые пользователи получают 14 дней бесплатного пробного периода с полным доступом ко всем функциям выбранного тарифа.</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-2">Какие методы оплаты доступны?</h3>
                <p class="text-gray-600">Мы принимаем оплату через Click, Payme, банковский перевод и международные карты Visa/Mastercard.</p>
            </div>
        </div>
    </div>

    <!-- Back to home -->
    <div class="max-w-7xl mx-auto mt-12 text-center">
        <a href="/" class="inline-flex items-center text-indigo-600 hover:text-indigo-700 font-medium">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            На главную
        </a>
    </div>
</div>


<div class="pwa-only min-h-screen bg-gray-50" x-data="plansPagePwa()">
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => 'Тарифы','backUrl' => '/']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Тарифы','backUrl' => '/']); ?>
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
        <div class="p-4 space-y-4" x-pull-to-refresh="loadPlans()">
            <!-- Billing Period Toggle -->
            <div class="native-card p-2">
                <div class="flex rounded-lg bg-gray-100 p-1">
                    <button class="flex-1 py-2 text-xs font-medium rounded-md transition-colors"
                            :class="billingPeriod === 'monthly' ? 'bg-white shadow text-indigo-600' : 'text-gray-600'"
                            @click="billingPeriod = 'monthly'">
                        Месяц
                    </button>
                    <button class="flex-1 py-2 text-xs font-medium rounded-md transition-colors"
                            :class="billingPeriod === 'quarterly' ? 'bg-white shadow text-indigo-600' : 'text-gray-600'"
                            @click="billingPeriod = 'quarterly'">
                        Квартал -10%
                    </button>
                    <button class="flex-1 py-2 text-xs font-medium rounded-md transition-colors"
                            :class="billingPeriod === 'yearly' ? 'bg-white shadow text-indigo-600' : 'text-gray-600'"
                            @click="billingPeriod = 'yearly'">
                        Год -20%
                    </button>
                </div>
            </div>

            <!-- Loading -->
            <template x-if="loading">
                <div class="native-card p-12 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-indigo-500 border-t-transparent"></div>
                    <p class="mt-3 text-gray-500 text-sm">Загрузка тарифов...</p>
                </div>
            </template>

            <!-- Plans List -->
            <div x-show="!loading" class="space-y-4">
                <template x-for="plan in plans" :key="plan.id">
                    <div class="native-card overflow-hidden" :class="plan.is_popular ? 'ring-2 ring-indigo-500' : ''">
                        <!-- Popular Badge -->
                        <div x-show="plan.is_popular" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-xs font-semibold text-center py-1">
                            Популярный
                        </div>
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-lg" x-text="plan.name"></h3>
                                    <p class="text-xs text-gray-500 mt-0.5" x-text="plan.description"></p>
                                </div>
                                <div class="text-right">
                                    <div class="text-xl font-bold text-gray-900" x-text="formatPrice(calculatePrice(plan))"></div>
                                    <div class="text-xs text-gray-500">/мес</div>
                                </div>
                            </div>

                            <!-- Limits -->
                            <div class="grid grid-cols-2 gap-2 text-xs mb-4">
                                <div class="flex items-center text-gray-700">
                                    <svg class="w-4 h-4 text-green-500 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-text="plan.limits.products + ' товаров'"></span>
                                </div>
                                <div class="flex items-center text-gray-700">
                                    <svg class="w-4 h-4 text-green-500 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-text="plan.limits.orders_per_month + ' заказов'"></span>
                                </div>
                                <div class="flex items-center text-gray-700">
                                    <svg class="w-4 h-4 text-green-500 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-text="plan.limits.marketplace_accounts + ' маркетплейсов'"></span>
                                </div>
                                <div class="flex items-center text-gray-700">
                                    <svg class="w-4 h-4 text-green-500 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-text="plan.limits.users + ' пользователей'"></span>
                                </div>
                            </div>

                            <!-- Select Button -->
                            <button @click="selectPlan(plan)"
                                    :class="plan.is_popular ? 'native-btn-primary' : ''"
                                    class="native-btn w-full">
                                Выбрать
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- FAQ -->
            <div class="space-y-3 mt-6">
                <div class="native-caption">Часто задаваемые вопросы</div>
                <div class="native-card p-4">
                    <h4 class="font-medium text-gray-900 text-sm mb-1">Можно ли сменить тариф?</h4>
                    <p class="text-xs text-gray-600">Да, вы можете повысить или понизить тариф в любой момент.</p>
                </div>
                <div class="native-card p-4">
                    <h4 class="font-medium text-gray-900 text-sm mb-1">Есть ли пробный период?</h4>
                    <p class="text-xs text-gray-600">Да, 14 дней бесплатного доступа ко всем функциям.</p>
                </div>
                <div class="native-card p-4">
                    <h4 class="font-medium text-gray-900 text-sm mb-1">Методы оплаты</h4>
                    <p class="text-xs text-gray-600">Click, Payme, банковский перевод и карты Visa/Mastercard.</p>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function plansPagePwa() {
    return {
        plans: [],
        loading: true,
        billingPeriod: 'monthly',

        async init() {
            await this.loadPlans();
        },

        async loadPlans() {
            try {
                const response = await fetch('/api/plans', { headers: { 'Accept': 'application/json' } });
                if (response.ok) {
                    const data = await response.json();
                    this.plans = data.plans || [];
                }
            } catch (error) {
                console.error('Error loading plans:', error);
            } finally {
                this.loading = false;
            }
        },

        calculatePrice(plan) {
            const basePrice = parseFloat(plan.price);
            if (this.billingPeriod === 'quarterly') return (basePrice * 0.9).toFixed(0);
            if (this.billingPeriod === 'yearly') return (basePrice * 0.8).toFixed(0);
            return basePrice;
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'UZS', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(price);
        },

        selectPlan(plan) {
            const token = localStorage.getItem('_x_auth_token');
            if (!token) {
                window.location.href = `/login?redirect=/plans&plan=${plan.slug}&period=${this.billingPeriod}`;
            } else {
                window.location.href = `/company/profile?tab=billing&plan=${plan.slug}&period=${this.billingPeriod}`;
            }
        }
    }
}
</script>

<script>
function plansPage() {
    return {
        plans: [],
        loading: true,
        billingPeriod: 'monthly',

        async init() {
            await this.loadPlans();
        },

        async loadPlans() {
            try {
                const response = await fetch('/api/plans', {
                    headers: {
                        'Accept': 'application/json',
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.plans = data.plans || [];
                }
            } catch (error) {
                console.error('Error loading plans:', error);
            } finally {
                this.loading = false;
            }
        },

        calculatePrice(plan) {
            const basePrice = parseFloat(plan.price);
            if (this.billingPeriod === 'quarterly') {
                return (basePrice * 0.9).toFixed(0); // 10% discount
            } else if (this.billingPeriod === 'yearly') {
                return (basePrice * 0.8).toFixed(0); // 20% discount
            }
            return basePrice;
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'UZS',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(price);
        },

        getBillingLabel() {
            if (this.billingPeriod === 'quarterly') {
                return 'Оплата раз в квартал (-10%)';
            } else if (this.billingPeriod === 'yearly') {
                return 'Оплата раз в год (-20%)';
            }
            return 'Ежемесячная оплата';
        },

        selectPlan(plan) {
            // Check if user is authenticated
            const token = localStorage.getItem('_x_auth_token');

            if (!token) {
                // Redirect to login
                window.location.href = `/login?redirect=/plans&plan=${plan.slug}&period=${this.billingPeriod}`;
            } else {
                // Redirect to subscription page with selected plan
                window.location.href = `/company/profile?tab=billing&plan=${plan.slug}&period=${this.billingPeriod}`;
            }
        }
    }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\plans\index.blade.php ENDPATH**/ ?>