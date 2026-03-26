@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    /* Uzum Market Brand Colors: Indigo #3A007D, Rose #F4488D, Yellow #FFFF04 */
    .uzum-gradient { background: linear-gradient(135deg, #3A007D 0%, #F4488D 100%); }
    .uzum-accent { color: #3A007D; }
    .uzum-bg-accent { background-color: #3A007D; }
    .uzum-border-accent { border-color: #3A007D; }
    .uzum-ring-accent:focus { --tw-ring-color: #3A007D; }
    .uzum-hover:hover { background-color: rgba(58, 0, 125, 0.1); }
    .uzum-rose { color: #F4488D; }
    .uzum-bg-rose { background-color: #F4488D; }
    .uzum-yellow { color: #FFFF04; }
    .uzum-bg-yellow { background-color: #FFFF04; }
    .uzum-btn { @apply bg-[#3A007D] hover:bg-[#2A0060] text-white; }
    .uzum-btn-outline { @apply border-2 border-[#3A007D] text-[#3A007D] hover:bg-[#3A007D] hover:text-white; }
</style>

<div x-data="uzumInvoicesPage()" x-init="init()" x-cloak class="flex h-screen bg-gray-50 browser-only"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">

    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden font-sans"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">

        <!-- Header -->
        <header class="bg-white border-b border-gray-200 shadow-sm">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="/marketplace/{{ $accountId }}" class="text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <div class="flex items-center space-x-3">
                            <!-- Uzum Logo -->
                            <div class="w-10 h-10 uzum-gradient rounded-xl flex items-center justify-center shadow-md">
                                <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h1 class="text-xl font-bold text-gray-900">Накладные и возвраты</h1>
                                    <!-- Вкладки -->
                                    <div class="flex items-center bg-gray-100 rounded-lg p-0.5">
                                        <button @click="switchTab('fbs')"
                                                class="px-3 py-1 text-xs font-semibold rounded-md transition"
                                                :class="activeTab === 'fbs' ? 'bg-[#3A007D] text-white' : 'text-gray-600 hover:text-gray-900'">
                                            FBS Накладные
                                        </button>
                                        <button @click="switchTab('supplies')"
                                                class="px-3 py-1 text-xs font-semibold rounded-md transition"
                                                :class="activeTab === 'supplies' ? 'bg-[#3A007D] text-white' : 'text-gray-600 hover:text-gray-900'">
                                            Поставки
                                        </button>
                                        <button @click="switchTab('returns')"
                                                class="px-3 py-1 text-xs font-semibold rounded-md transition"
                                                :class="activeTab === 'returns' ? 'bg-[#3A007D] text-white' : 'text-gray-600 hover:text-gray-900'">
                                            Возвраты
                                        </button>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500">{{ $accountName ?? 'Uzum Market' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Действия -->
                    <div class="flex items-center space-x-3">
                        <!-- Кнопка создания накладной (только FBS) -->
                        <button x-show="activeTab === 'fbs'"
                                @click="showCreateModal = true"
                                class="uzum-btn px-4 py-2 rounded-xl text-sm font-medium flex items-center space-x-2 transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Создать накладную</span>
                        </button>

                        <!-- Индикатор загрузки -->
                        <div x-show="loading" class="flex items-center space-x-2 px-3 py-1.5 bg-blue-50 rounded-full">
                            <svg class="w-4 h-4 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span class="text-xs text-blue-700 font-medium">Загрузка...</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Контент -->
        <main class="flex-1 overflow-y-auto p-6">

            <!-- ================ FBS Накладные ================ -->
            <div x-show="activeTab === 'fbs'">

                <!-- Пустое состояние -->
                <template x-if="!loading && fbsInvoices.length === 0">
                    <div class="flex flex-col items-center justify-center py-20">
                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-gray-500 text-lg font-medium">Нет накладных</p>
                        <p class="text-gray-400 text-sm mt-1">Создайте первую FBS накладную</p>
                    </div>
                </template>

                <!-- Таблица FBS накладных -->
                <template x-if="fbsInvoices.length > 0">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Статус</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Дата</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Заказов</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Действия</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="invoice in fbsInvoices" :key="invoice.id">
                                        <tr @click="viewDetail(invoice.id, 'fbs')"
                                            class="hover:bg-gray-50 cursor-pointer transition">
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="invoice.id"></td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      :class="{
                                                          'bg-green-100 text-green-800': invoice.status === 'completed',
                                                          'bg-yellow-100 text-yellow-800': invoice.status === 'pending',
                                                          'bg-blue-100 text-blue-800': invoice.status === 'processing',
                                                          'bg-gray-100 text-gray-800': !['completed','pending','processing'].includes(invoice.status)
                                                      }"
                                                      x-text="invoice.status_label || invoice.status"></span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600" x-text="invoice.date || invoice.created_at"></td>
                                            <td class="px-6 py-4 text-sm text-gray-600" x-text="invoice.orders_count ?? '-'"></td>
                                            <td class="px-6 py-4 text-right" @click.stop>
                                                <button @click="downloadClosingDocs(invoice.id)"
                                                        class="uzum-btn-outline px-3 py-1 rounded-lg text-xs font-medium transition">
                                                    Акт приёмки
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Пагинация -->
                        <div x-show="totalPages > 1" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                            <p class="text-sm text-gray-500">
                                Страница <span x-text="page + 1"></span> из <span x-text="totalPages"></span>
                            </p>
                            <div class="flex items-center space-x-2">
                                <button @click="page = Math.max(0, page - 1); loadFbsInvoices()"
                                        :disabled="page === 0"
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-300 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
                                    Назад
                                </button>
                                <button @click="page = Math.min(totalPages - 1, page + 1); loadFbsInvoices()"
                                        :disabled="page >= totalPages - 1"
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-300 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
                                    Далее
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ================ Поставки ================ -->
            <div x-show="activeTab === 'supplies'">

                <!-- Выбор магазина -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Магазин</label>
                    <select @change="loadShopInvoices($event.target.value)"
                            x-model="selectedShop"
                            class="w-full max-w-xs px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#3A007D] focus:border-transparent transition">
                        <option value="">-- Выберите магазин --</option>
                        @foreach($uzumShops as $shop)
                            <option value="{{ $shop->id }}">{{ $shop->name }} ({{ $shop->external_id }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Пустое состояние -->
                <template x-if="!loading && shopInvoicesList.length === 0">
                    <div class="flex flex-col items-center justify-center py-20">
                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p class="text-gray-500 text-lg font-medium">Нет поставок</p>
                        <p class="text-gray-400 text-sm mt-1" x-text="selectedShop ? 'Накладные поставок не найдены' : 'Выберите магазин для просмотра'"></p>
                    </div>
                </template>

                <!-- Таблица поставок -->
                <template x-if="shopInvoicesList.length > 0">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Статус</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Дата</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Товаров</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="inv in shopInvoicesList" :key="inv.id">
                                        <tr @click="viewDetail(inv.id, 'supply')"
                                            class="hover:bg-gray-50 cursor-pointer transition">
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="inv.id"></td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      :class="{
                                                          'bg-green-100 text-green-800': inv.status === 'completed',
                                                          'bg-yellow-100 text-yellow-800': inv.status === 'pending',
                                                          'bg-blue-100 text-blue-800': inv.status === 'processing',
                                                          'bg-gray-100 text-gray-800': !['completed','pending','processing'].includes(inv.status)
                                                      }"
                                                      x-text="inv.status_label || inv.status"></span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600" x-text="inv.date || inv.created_at"></td>
                                            <td class="px-6 py-4 text-sm text-gray-600" x-text="inv.items_count ?? '-'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ================ Возвраты ================ -->
            <div x-show="activeTab === 'returns'">

                <!-- Пустое состояние -->
                <template x-if="!loading && returnsList.length === 0">
                    <div class="flex flex-col items-center justify-center py-20">
                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        <p class="text-gray-500 text-lg font-medium">Нет возвратов</p>
                        <p class="text-gray-400 text-sm mt-1">Возвраты пока отсутствуют</p>
                    </div>
                </template>

                <!-- Таблица возвратов -->
                <template x-if="returnsList.length > 0">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Статус</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Дата</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Товар</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Причина</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="ret in returnsList" :key="ret.id">
                                        <tr @click="viewDetail(ret.id, 'return')"
                                            class="hover:bg-gray-50 cursor-pointer transition">
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="ret.id"></td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      :class="{
                                                          'bg-red-100 text-red-800': ret.status === 'returned',
                                                          'bg-yellow-100 text-yellow-800': ret.status === 'pending',
                                                          'bg-blue-100 text-blue-800': ret.status === 'processing',
                                                          'bg-gray-100 text-gray-800': !['returned','pending','processing'].includes(ret.status)
                                                      }"
                                                      x-text="ret.status_label || ret.status"></span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600" x-text="ret.date || ret.created_at"></td>
                                            <td class="px-6 py-4 text-sm text-gray-600" x-text="ret.product_name || '-'"></td>
                                            <td class="px-6 py-4 text-sm text-gray-600" x-text="ret.reason || '-'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

        </main>
    </div>

    <!-- Нижняя навигация -->
    <template x-if="$store.ui.navPosition === 'bottom' || $store.ui.navPosition === 'top'">
        <x-sidebar />
    </template>

    <!-- ================ Модалка деталей ================ -->
    <div x-show="showDetailModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="showDetailModal = false">

        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/50" @click="showDetailModal = false"></div>

        <!-- Карточка -->
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] overflow-hidden flex flex-col"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             @click.stop>

            <!-- Заголовок модалки -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">
                        <span x-text="detailData.type === 'fbs' ? 'FBS Накладная' : (detailData.type === 'supply' ? 'Поставка' : 'Возврат')"></span>
                        #<span x-text="detailData.id"></span>
                    </h3>
                    <p class="text-sm text-gray-500" x-text="detailData.date || detailData.created_at"></p>
                </div>
                <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Содержимое модалки -->
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                <!-- Информация -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Статус</p>
                        <p class="text-sm font-semibold text-gray-900" x-text="detailData.status_label || detailData.status || '-'"></p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Дата создания</p>
                        <p class="text-sm font-semibold text-gray-900" x-text="detailData.date || detailData.created_at || '-'"></p>
                    </div>
                    <div x-show="detailData.orders_count !== undefined" class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Заказов</p>
                        <p class="text-sm font-semibold text-gray-900" x-text="detailData.orders_count"></p>
                    </div>
                    <div x-show="detailData.items_count !== undefined" class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Товаров</p>
                        <p class="text-sm font-semibold text-gray-900" x-text="detailData.items_count"></p>
                    </div>
                </div>

                <!-- Список заказов -->
                <template x-if="detailOrders.length > 0">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Заказы в накладной</h4>
                        <div class="space-y-2">
                            <template x-for="order in detailOrders" :key="order.id">
                                <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900" x-text="'#' + order.id"></p>
                                        <p class="text-xs text-gray-500" x-text="order.product_name || order.sku || '-'"></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-gray-900" x-text="order.quantity ? order.quantity + ' шт.' : '-'"></p>
                                        <p class="text-xs text-gray-500" x-text="order.price ? order.price + ' сум' : ''"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Футер модалки -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-end space-x-3">
                <button x-show="detailData.type === 'fbs'"
                        @click="downloadClosingDocs(detailData.id)"
                        class="uzum-btn px-4 py-2 rounded-xl text-sm font-medium transition">
                    Акт приёмки
                </button>
                <button @click="showDetailModal = false"
                        class="px-4 py-2 rounded-xl text-sm font-medium border border-gray-300 bg-white hover:bg-gray-50 transition">
                    Закрыть
                </button>
            </div>
        </div>
    </div>

    <!-- ================ Модалка создания накладной ================ -->
    <div x-show="showCreateModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="showCreateModal = false">

        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/50" @click="showCreateModal = false"></div>

        <!-- Карточка -->
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             @click.stop>

            <!-- Заголовок -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                <h3 class="text-lg font-bold text-gray-900">Создать FBS накладную</h3>
                <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Форма -->
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Магазин</label>
                    <select x-model="createForm.shopId"
                            class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#3A007D] focus:border-transparent transition">
                        <option value="">-- Выберите магазин --</option>
                        @foreach($uzumShops as $shop)
                            <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Комментарий (необязательно)</label>
                    <textarea x-model="createForm.comment"
                              rows="3"
                              placeholder="Примечание к накладной..."
                              class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#3A007D] focus:border-transparent transition resize-none"></textarea>
                </div>
            </div>

            <!-- Кнопки -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-end space-x-3">
                <button @click="showCreateModal = false"
                        class="px-4 py-2 rounded-xl text-sm font-medium border border-gray-300 bg-white hover:bg-gray-50 transition">
                    Отмена
                </button>
                <button @click="createInvoice()"
                        :disabled="!createForm.shopId || loading"
                        class="uzum-btn px-4 py-2 rounded-xl text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Создать
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function uzumInvoicesPage() {
    return {
        accountId: @json($accountId),
        activeTab: 'fbs',
        loading: false,

        // Данные
        fbsInvoices: [],
        shopInvoicesList: [],
        returnsList: [],

        // Фильтры и пагинация
        selectedShop: '',
        page: 0,
        totalPages: 0,

        // Модалки
        showDetailModal: false,
        showCreateModal: false,

        // Детали
        detailData: {},
        detailOrders: [],

        // Форма создания
        createForm: {
            shopId: '',
            comment: ''
        },

        /**
         * Базовый URL для API запросов
         */
        get apiBase() {
            return `/api/marketplace/uzum/accounts/${this.accountId}`;
        },

        /**
         * Заголовки для fetch запросов
         */
        get fetchHeaders() {
            return {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            };
        },

        /**
         * Инициализация компонента
         */
        init() {
            this.loadFbsInvoices();
        },

        /**
         * Переключение вкладки
         */
        switchTab(tab) {
            this.activeTab = tab;
            if (tab === 'fbs') {
                this.loadFbsInvoices();
            } else if (tab === 'supplies') {
                if (this.selectedShop) {
                    this.loadShopInvoices(this.selectedShop);
                }
            } else if (tab === 'returns') {
                this.loadReturns();
            }
        },

        /**
         * Загрузка FBS накладных
         */
        async loadFbsInvoices() {
            this.loading = true;
            try {
                const response = await fetch(`${this.apiBase}/invoices/fbs?page=${this.page}`, {
                    headers: this.fetchHeaders
                });
                if (!response.ok) throw new Error('Ошибка загрузки FBS накладных');
                const data = await response.json();
                this.fbsInvoices = data.data || data.items || [];
                this.totalPages = data.total_pages || data.last_page || 1;
            } catch (error) {
                console.error('loadFbsInvoices:', error);
                alert('Не удалось загрузить FBS накладные: ' + error.message);
                this.fbsInvoices = [];
            } finally {
                this.loading = false;
            }
        },

        /**
         * Загрузка накладных поставки для выбранного магазина
         */
        async loadShopInvoices(shopId) {
            if (!shopId) {
                this.shopInvoicesList = [];
                return;
            }
            this.loading = true;
            try {
                const response = await fetch(`${this.apiBase}/invoices/shop/${shopId}`, {
                    headers: this.fetchHeaders
                });
                if (!response.ok) throw new Error('Ошибка загрузки поставок');
                const data = await response.json();
                this.shopInvoicesList = data.data || data.items || [];
            } catch (error) {
                console.error('loadShopInvoices:', error);
                alert('Не удалось загрузить поставки: ' + error.message);
                this.shopInvoicesList = [];
            } finally {
                this.loading = false;
            }
        },

        /**
         * Загрузка возвратов
         */
        async loadReturns() {
            this.loading = true;
            try {
                const response = await fetch(`${this.apiBase}/returns/shop/${this.selectedShop}`, {
                    headers: this.fetchHeaders
                });
                if (!response.ok) throw new Error('Ошибка загрузки возвратов');
                const data = await response.json();
                this.returnsList = data.data || data.items || [];
            } catch (error) {
                console.error('loadReturns:', error);
                alert('Не удалось загрузить возвраты: ' + error.message);
                this.returnsList = [];
            } finally {
                this.loading = false;
            }
        },

        /**
         * Просмотр деталей накладной/поставки/возврата
         */
        async viewDetail(id, type) {
            this.loading = true;
            try {
                let url;
                if (type === 'fbs') {
                    url = `${this.apiBase}/invoices/fbs/${id}`;
                } else if (type === 'supply') {
                    url = `${this.apiBase}/invoices/shop/${this.selectedShop}/products`;
                } else {
                    url = `${this.apiBase}/returns/shop/${this.selectedShop}/${id}`;
                }

                const response = await fetch(url, {
                    headers: this.fetchHeaders
                });
                if (!response.ok) throw new Error('Ошибка загрузки деталей');
                const data = await response.json();

                this.detailData = { ...data, type: type };
                this.detailOrders = data.orders || data.items || [];
                this.showDetailModal = true;
            } catch (error) {
                console.error('viewDetail:', error);
                alert('Не удалось загрузить детали: ' + error.message);
            } finally {
                this.loading = false;
            }
        },

        /**
         * Создание новой FBS накладной
         */
        async createInvoice() {
            if (!this.createForm.shopId) {
                alert('Выберите магазин');
                return;
            }
            this.loading = true;
            try {
                const response = await fetch(`${this.apiBase}/invoices/fbs`, {
                    method: 'POST',
                    headers: this.fetchHeaders,
                    body: JSON.stringify({
                        shop_id: this.createForm.shopId,
                        comment: this.createForm.comment
                    })
                });
                if (!response.ok) throw new Error('Ошибка создания накладной');

                this.showCreateModal = false;
                this.createForm = { shopId: '', comment: '' };
                this.page = 0;
                await this.loadFbsInvoices();
            } catch (error) {
                console.error('createInvoice:', error);
                alert('Не удалось создать накладную: ' + error.message);
            } finally {
                this.loading = false;
            }
        },

        /**
         * Скачивание акта приёмки (закрывающие документы)
         */
        async downloadClosingDocs(id) {
            try {
                const response = await fetch(`${this.apiBase}/invoices/fbs/${id}/closing-docs`, {
                    headers: this.fetchHeaders
                });
                if (!response.ok) throw new Error('Ошибка получения акта приёмки');

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `act_${id}.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error('downloadClosingDocs:', error);
                alert('Не удалось скачать акт приёмки: ' + error.message);
            }
        }
    };
}
</script>
@endsection
