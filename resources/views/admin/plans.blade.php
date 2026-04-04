@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8" x-data="adminPlansPage()">

    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Управление тарифами</h1>
                <p class="mt-1 text-gray-600">Создание, редактирование и управление тарифными планами</p>
            </div>
            <button @click="openCreate()"
                    class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Добавить тариф
            </button>
        </div>
    </div>

    <!-- Access Denied -->
    <template x-if="accessDenied">
        <div class="max-w-7xl mx-auto">
            <div class="bg-red-50 border border-red-200 rounded-xl p-8 text-center">
                <svg class="w-12 h-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <h3 class="text-lg font-semibold text-red-800">Доступ запрещён</h3>
                <p class="text-red-600 mt-1">Эта страница доступна только администраторам.</p>
            </div>
        </div>
    </template>

    <!-- Loading -->
    <template x-if="loading && !accessDenied">
        <div class="max-w-7xl mx-auto text-center py-20">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-indigo-500 border-t-transparent"></div>
            <p class="mt-4 text-gray-600">Загрузка тарифов...</p>
        </div>
    </template>

    <!-- Plans Table -->
    <div x-show="!loading && !accessDenied" class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">#</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Название</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Цена</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Лимиты</th>
                            <th class="text-center py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Функции</th>
                            <th class="text-center py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Подписчики</th>
                            <th class="text-center py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Статус</th>
                            <th class="text-right py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="plan in plans" :key="plan.id">
                            <tr class="hover:bg-gray-50 transition-colors" :class="!plan.is_active ? 'opacity-50' : ''">
                                <!-- Sort Order -->
                                <td class="py-3 px-4">
                                    <span class="text-sm text-gray-500 font-mono" x-text="plan.sort_order"></span>
                                </td>
                                <!-- Name -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <div class="font-semibold text-gray-900" x-text="plan.name"></div>
                                            <div class="text-xs text-gray-400 font-mono" x-text="plan.slug"></div>
                                        </div>
                                        <span x-show="plan.is_popular" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                            Popular
                                        </span>
                                    </div>
                                </td>
                                <!-- Price -->
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-gray-900" x-text="formatPrice(plan.price)"></div>
                                    <div class="text-xs text-gray-500" x-text="plan.billing_period === 'monthly' ? '/мес' : plan.billing_period === 'quarterly' ? '/квартал' : '/год'"></div>
                                </td>
                                <!-- Limits -->
                                <td class="py-3 px-4">
                                    <div class="text-xs space-y-0.5 text-gray-600">
                                        <div><span class="font-medium" x-text="plan.max_products >= 999999 ? 'Безлимит' : plan.max_products.toLocaleString()"></span> товаров</div>
                                        <div><span class="font-medium" x-text="plan.max_orders_per_month >= 999999 ? 'Безлимит' : plan.max_orders_per_month.toLocaleString()"></span> заказов</div>
                                        <div><span class="font-medium" x-text="plan.max_marketplace_accounts >= 999 ? 'Безлимит' : plan.max_marketplace_accounts"></span> МП</div>
                                        <div><span class="font-medium" x-text="plan.max_users >= 999 ? 'Безлимит' : plan.max_users"></span> юзеров</div>
                                        <div><span class="font-medium" x-text="plan.max_ai_requests >= 99999 ? 'Безлимит' : plan.max_ai_requests.toLocaleString()"></span> AI</div>
                                    </div>
                                </td>
                                <!-- Features -->
                                <td class="py-3 px-4 text-center">
                                    <div class="flex flex-wrap gap-1 justify-center">
                                        <span x-show="plan.has_api_access" class="px-1.5 py-0.5 text-xs rounded bg-green-100 text-green-700" title="API">API</span>
                                        <span x-show="plan.has_priority_support" class="px-1.5 py-0.5 text-xs rounded bg-blue-100 text-blue-700" title="Приоритетная поддержка">VIP</span>
                                        <span x-show="plan.has_telegram_notifications" class="px-1.5 py-0.5 text-xs rounded bg-cyan-100 text-cyan-700" title="Telegram">TG</span>
                                        <span x-show="plan.has_auto_pricing" class="px-1.5 py-0.5 text-xs rounded bg-yellow-100 text-yellow-700" title="Автоценообразование">AP</span>
                                        <span x-show="plan.has_analytics" class="px-1.5 py-0.5 text-xs rounded bg-purple-100 text-purple-700" title="Аналитика">AN</span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-400" x-text="(plan.allowed_marketplaces || []).join(', ')"></div>
                                </td>
                                <!-- Subscribers -->
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-1 rounded-full text-sm font-medium"
                                          :class="plan.active_subscribers_count > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                          x-text="plan.active_subscribers_count || 0"></span>
                                </td>
                                <!-- Status -->
                                <td class="py-3 px-4 text-center">
                                    <button @click="toggleActive(plan)" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                            :class="plan.is_active ? 'bg-green-500' : 'bg-gray-300'">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm"
                                              :class="plan.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
                                    </button>
                                </td>
                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEdit(plan)" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Редактировать">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button @click="deletePlan(plan)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Удалить"
                                                :disabled="plan.active_subscribers_count > 0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div x-show="plans.length === 0 && !loading" class="py-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-500">Тарифы не найдены</p>
                <button @click="openCreate()" class="mt-4 text-indigo-600 hover:text-indigo-700 font-medium">Создать первый тариф</button>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="fixed inset-0 bg-gray-900/75" @click="showModal = false"></div>
        <div class="flex min-h-screen items-start justify-center p-4 pt-16">
            <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl" @click.stop>
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900" x-text="editingPlan ? 'Редактировать тариф' : 'Новый тариф'"></h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="px-6 pt-4 border-b border-gray-200">
                    <div class="flex gap-6">
                        <button @click="activeTab = 'basic'" class="pb-3 text-sm font-medium border-b-2 transition-colors"
                                :class="activeTab === 'basic' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                            Основное
                        </button>
                        <button @click="activeTab = 'limits'" class="pb-3 text-sm font-medium border-b-2 transition-colors"
                                :class="activeTab === 'limits' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                            Лимиты
                        </button>
                        <button @click="activeTab = 'features'" class="pb-3 text-sm font-medium border-b-2 transition-colors"
                                :class="activeTab === 'features' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                            Функции
                        </button>
                        <button @click="activeTab = 'description'" class="pb-3 text-sm font-medium border-b-2 transition-colors"
                                :class="activeTab === 'description' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                            Описание фич
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 max-h-[60vh] overflow-y-auto">
                    <!-- Error -->
                    <div x-show="errorMessage" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700" x-text="errorMessage"></div>

                    <!-- Tab: Basic -->
                    <div x-show="activeTab === 'basic'" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Название <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.name" @input="if(!editingPlan) form.slug = slugify(form.name)"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       placeholder="Например: VIP">
                                <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-600"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Slug <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.slug"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono"
                                       placeholder="vip">
                                <p x-show="errors.slug" x-text="errors.slug" class="mt-1 text-xs text-red-600"></p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                            <textarea x-model="form.description" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                      placeholder="Краткое описание тарифа"></textarea>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Цена <span class="text-red-500">*</span></label>
                                <input type="number" x-model.number="form.price" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Валюта</label>
                                <select x-model="form.currency" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="UZS">UZS</option>
                                    <option value="USD">USD</option>
                                    <option value="RUB">RUB</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Период</label>
                                <select x-model="form.billing_period" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="monthly">Ежемесячно</option>
                                    <option value="quarterly">Ежеквартально</option>
                                    <option value="yearly">Ежегодно</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Порядок сортировки</label>
                                <input type="number" x-model.number="form.sort_order" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="form.is_active" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Активный</span>
                                </label>
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="form.is_popular" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Популярный</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Limits -->
                    <div x-show="activeTab === 'limits'" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Макс. маркетплейсов</label>
                                <input type="number" x-model.number="form.max_marketplace_accounts" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Макс. товаров</label>
                                <input type="number" x-model.number="form.max_products" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Макс. заказов/мес</label>
                                <input type="number" x-model.number="form.max_orders_per_month" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Макс. пользователей</label>
                                <input type="number" x-model.number="form.max_users" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Макс. складов</label>
                                <input type="number" x-model.number="form.max_warehouses" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Макс. AI-запросов</label>
                                <input type="number" x-model.number="form.max_ai_requests" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Хранение данных (дней)</label>
                                <input type="number" x-model.number="form.data_retention_days" min="1"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Используйте 999 или 999999 для безлимитных значений.</p>
                    </div>

                    <!-- Tab: Features -->
                    <div x-show="activeTab === 'features'" class="space-y-6">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Доступные функции</h4>
                            <div class="space-y-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" x-model="form.has_api_access" class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                                    <span class="text-sm text-gray-700">API доступ</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" x-model="form.has_priority_support" class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                                    <span class="text-sm text-gray-700">Приоритетная поддержка</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" x-model="form.has_telegram_notifications" class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                                    <span class="text-sm text-gray-700">Telegram уведомления</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" x-model="form.has_auto_pricing" class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                                    <span class="text-sm text-gray-700">Автоценообразование</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" x-model="form.has_analytics" class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                                    <span class="text-sm text-gray-700">Расширенная аналитика</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Доступные маркетплейсы</h4>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" value="uzum" x-model="form.allowed_marketplaces" class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                                    <span class="text-sm text-gray-700">Uzum</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" value="wb" x-model="form.allowed_marketplaces" class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                                    <span class="text-sm text-gray-700">Wildberries</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" value="ozon" x-model="form.allowed_marketplaces" class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                                    <span class="text-sm text-gray-700">Ozon</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" value="yandex" x-model="form.allowed_marketplaces" class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                                    <span class="text-sm text-gray-700">Yandex Market</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Feature Description List -->
                    <div x-show="activeTab === 'description'" class="space-y-4">
                        <p class="text-sm text-gray-600">Список фич отображается на странице тарифов для клиентов.</p>
                        <template x-for="(feature, index) in form.features" :key="index">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="form.features[index]"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                       placeholder="Описание фичи">
                                <button @click="form.features.splice(index, 1)" class="p-2 text-gray-400 hover:text-red-500 transition-colors" title="Удалить">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <button @click="form.features.push('')" class="inline-flex items-center px-3 py-2 text-sm text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Добавить фичу
                        </button>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                    <button @click="showModal = false" :disabled="saving"
                            class="px-4 py-2 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        Отмена
                    </button>
                    <button @click="savePlan()" :disabled="saving"
                            class="inline-flex items-center px-5 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50">
                        <svg x-show="saving" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="saving ? 'Сохранение...' : (editingPlan ? 'Сохранить' : 'Создать')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function adminPlansPage() {
    return {
        plans: [],
        loading: true,
        accessDenied: false,
        showModal: false,
        editingPlan: null,
        activeTab: 'basic',
        saving: false,
        errorMessage: '',
        errors: {},
        form: this.defaultForm(),

        defaultForm() {
            return {
                name: '',
                slug: '',
                description: '',
                price: 0,
                currency: 'UZS',
                billing_period: 'monthly',
                max_marketplace_accounts: 1,
                max_products: 100,
                max_orders_per_month: 300,
                max_users: 1,
                max_warehouses: 1,
                max_ai_requests: 30,
                data_retention_days: 30,
                has_api_access: false,
                has_priority_support: false,
                has_telegram_notifications: true,
                has_auto_pricing: false,
                has_analytics: false,
                allowed_marketplaces: ['uzum'],
                features: [],
                sort_order: 0,
                is_active: true,
                is_popular: false,
            };
        },

        async init() {
            await this.loadPlans();
        },

        getHeaders() {
            return {
                'Authorization': 'Bearer ' + localStorage.getItem('_x_auth_token'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };
        },

        async loadPlans() {
            this.loading = true;
            try {
                const response = await fetch('/api/admin/plans', { headers: this.getHeaders() });
                if (response.status === 403) {
                    this.accessDenied = true;
                    return;
                }
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

        openCreate() {
            this.editingPlan = null;
            this.form = this.defaultForm();
            this.form.sort_order = this.plans.length + 1;
            this.errors = {};
            this.errorMessage = '';
            this.activeTab = 'basic';
            this.showModal = true;
        },

        openEdit(plan) {
            this.editingPlan = plan;
            this.form = {
                name: plan.name,
                slug: plan.slug,
                description: plan.description || '',
                price: parseFloat(plan.price),
                currency: plan.currency,
                billing_period: plan.billing_period,
                max_marketplace_accounts: plan.max_marketplace_accounts,
                max_products: plan.max_products,
                max_orders_per_month: plan.max_orders_per_month,
                max_users: plan.max_users,
                max_warehouses: plan.max_warehouses,
                max_ai_requests: plan.max_ai_requests,
                data_retention_days: plan.data_retention_days,
                has_api_access: plan.has_api_access,
                has_priority_support: plan.has_priority_support,
                has_telegram_notifications: plan.has_telegram_notifications,
                has_auto_pricing: plan.has_auto_pricing,
                has_analytics: plan.has_analytics,
                allowed_marketplaces: [...(plan.allowed_marketplaces || [])],
                features: [...(plan.features || [])],
                sort_order: plan.sort_order,
                is_active: plan.is_active,
                is_popular: plan.is_popular,
            };
            this.errors = {};
            this.errorMessage = '';
            this.activeTab = 'basic';
            this.showModal = true;
        },

        async savePlan() {
            this.saving = true;
            this.errors = {};
            this.errorMessage = '';

            // Filter out empty features
            this.form.features = this.form.features.filter(f => f.trim() !== '');

            const url = this.editingPlan ? `/api/admin/plans/${this.editingPlan.id}` : '/api/admin/plans';
            const method = this.editingPlan ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method,
                    headers: this.getHeaders(),
                    body: JSON.stringify(this.form),
                });

                const data = await response.json();

                if (!response.ok) {
                    if (data.errors) {
                        this.errors = {};
                        for (const [key, msgs] of Object.entries(data.errors)) {
                            this.errors[key] = Array.isArray(msgs) ? msgs[0] : msgs;
                        }
                    }
                    this.errorMessage = data.message || 'Ошибка сохранения';
                    return;
                }

                this.showModal = false;
                await this.loadPlans();
            } catch (error) {
                this.errorMessage = 'Ошибка сети';
                console.error('Save error:', error);
            } finally {
                this.saving = false;
            }
        },

        async deletePlan(plan) {
            if (!confirm(`Удалить тариф "${plan.name}"? Это действие необратимо.`)) return;

            try {
                const response = await fetch(`/api/admin/plans/${plan.id}`, {
                    method: 'DELETE',
                    headers: this.getHeaders(),
                });

                if (response.status === 409) {
                    const data = await response.json();
                    alert(data.message);
                    return;
                }

                if (response.ok || response.status === 204) {
                    await this.loadPlans();
                }
            } catch (error) {
                console.error('Delete error:', error);
            }
        },

        async toggleActive(plan) {
            try {
                const response = await fetch(`/api/admin/plans/${plan.id}/toggle-active`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                });

                if (response.ok) {
                    const data = await response.json();
                    const idx = this.plans.findIndex(p => p.id === plan.id);
                    if (idx !== -1) {
                        this.plans[idx].is_active = data.plan.is_active;
                    }
                }
            } catch (error) {
                console.error('Toggle error:', error);
            }
        },

        slugify(text) {
            const translitMap = {
                'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'yo','ж':'zh',
                'з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o',
                'п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'kh','ц':'ts',
                'ч':'ch','ш':'sh','щ':'shch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya',
            };
            return text.toLowerCase()
                .split('')
                .map(c => translitMap[c] || c)
                .join('')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'UZS', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(price);
        },
    };
}
</script>
@endsection
