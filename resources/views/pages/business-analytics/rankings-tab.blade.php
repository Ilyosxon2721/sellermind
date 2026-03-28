{{-- Рейтинг товаров по продажам и маржинальности --}}
<div>
    {{-- Переключатель режима --}}
    <div class="flex space-x-1 bg-gray-100 rounded-lg p-1 mb-6">
        <button @click="rankingMode = 'sales'; loadSalesRanking()"
                :class="rankingMode === 'sales' ? 'bg-white shadow text-blue-700' : 'text-gray-600 hover:text-gray-900'"
                class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition">
            По продажам
        </button>
        <button @click="rankingMode = 'margin'; loadMarginRanking()"
                :class="rankingMode === 'margin' ? 'bg-white shadow text-blue-700' : 'text-gray-600 hover:text-gray-900'"
                class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition">
            По маржинальности
        </button>
    </div>

    {{-- Загрузка --}}
    <div x-show="loading" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <template x-for="i in 3" :key="i">
                <div class="bg-white rounded-xl p-6 animate-pulse">
                    <div class="h-4 bg-gray-200 rounded w-1/2 mb-3"></div>
                    <div class="h-8 bg-gray-200 rounded w-3/4"></div>
                </div>
            </template>
        </div>
        <div class="bg-white rounded-xl p-6 animate-pulse">
            <div class="h-64 bg-gray-200 rounded"></div>
        </div>
    </div>

    {{-- ===== РЕЙТИНГ ПО ПРОДАЖАМ ===== --}}
    <div x-show="!loading && rankingMode === 'sales'" class="space-y-6">
        {{-- Сводка --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-sm text-gray-500">Всего товаров</p>
                <p class="text-3xl font-bold text-gray-900 mt-1" x-text="salesData.summary.total_products"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-sm text-gray-500">Продано единиц</p>
                <p class="text-3xl font-bold text-blue-600 mt-1" x-text="formatMoney(salesData.summary.total_quantity)"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-sm text-gray-500">Общая выручка</p>
                <p class="text-3xl font-bold text-green-600 mt-1" x-text="formatMoney(salesData.summary.total_revenue) + ' сум'"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-sm text-gray-500">Среднее на товар</p>
                <p class="text-3xl font-bold text-purple-600 mt-1" x-text="salesData.summary.avg_items_per_product + ' шт'"></p>
            </div>
        </div>

        {{-- Графики --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Топ-10 по количеству продаж</h3>
                <div class="relative" style="height: 320px;">
                    <canvas id="salesTopChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Доля в продажах</h3>
                <div class="relative" style="height: 320px;">
                    <canvas id="salesShareChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Таблица --}}
        <div x-show="salesData.products.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Рейтинг товаров по продажам</h3>
                <p class="text-sm text-gray-500" x-text="'Всего: ' + salesData.products.length + ' товаров'"></p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Товар</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">SKU</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Продано</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Выручка</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Сред. цена</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Доля %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(product, index) in getSalesPagedProducts()" :key="index">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm text-gray-500">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold"
                                          :class="product.rank <= 3 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600'"
                                          x-text="product.rank"></span>
                                </td>
                                <td class="px-6 py-3 text-sm font-medium text-gray-900 max-w-xs truncate" x-text="product.product_name"></td>
                                <td class="px-6 py-3 text-sm text-gray-500 font-mono" x-text="product.sku"></td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-blue-600" x-text="formatMoney(product.quantity) + ' шт'"></td>
                                <td class="px-6 py-3 text-right text-sm font-medium text-gray-900" x-text="formatMoney(product.revenue)"></td>
                                <td class="px-6 py-3 text-right text-sm text-gray-600" x-text="formatMoney(product.avg_price)"></td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <div class="w-16 bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-500 h-2 rounded-full" :style="'width:' + Math.min(product.share_percent, 100) + '%'"></div>
                                        </div>
                                        <span class="text-sm text-gray-600" x-text="product.share_percent + '%'"></span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Пагинация --}}
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Показано <span x-text="Math.min((salesPage - 1) * salesPerPage + 1, salesData.products.length)"></span>–<span x-text="Math.min(salesPage * salesPerPage, salesData.products.length)"></span>
                    из <span x-text="salesData.products.length"></span>
                </div>
                <div class="flex items-center space-x-1">
                    <button @click="salesPage = 1" :disabled="salesPage === 1"
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">&laquo;</button>
                    <button @click="salesPage = Math.max(1, salesPage - 1)" :disabled="salesPage === 1"
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">&lsaquo;</button>
                    <span class="px-3 py-1.5 text-sm font-medium"><span x-text="salesPage"></span> / <span x-text="salesTotalPages()"></span></span>
                    <button @click="salesPage = Math.min(salesTotalPages(), salesPage + 1)" :disabled="salesPage >= salesTotalPages()"
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">&rsaquo;</button>
                    <button @click="salesPage = salesTotalPages()" :disabled="salesPage >= salesTotalPages()"
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">&raquo;</button>
                    <select x-model.number="salesPerPage" @change="salesPage = 1"
                            class="ml-2 px-2 py-1.5 text-sm border border-gray-300 rounded-lg">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Пусто --}}
        <div x-show="salesData.products.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <p class="text-gray-500">Нет данных о продажах за выбранный период</p>
        </div>
    </div>

    {{-- ===== РЕЙТИНГ ПО МАРЖИНАЛЬНОСТИ ===== --}}
    <div x-show="!loading && rankingMode === 'margin'" class="space-y-6">
        {{-- Сводка --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-sm text-gray-500">Общая выручка</p>
                <p class="text-3xl font-bold text-gray-900 mt-1" x-text="formatMoney(marginData.summary.total_revenue) + ' сум'"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-sm text-gray-500">Себестоимость</p>
                <p class="text-3xl font-bold text-red-500 mt-1" x-text="formatMoney(marginData.summary.total_cost) + ' сум'"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-sm text-gray-500">Прибыль</p>
                <p class="text-3xl font-bold mt-1" :class="marginData.summary.total_profit >= 0 ? 'text-green-600' : 'text-red-600'"
                   x-text="formatMoney(marginData.summary.total_profit) + ' сум'"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-sm text-gray-500">Средняя маржа</p>
                <p class="text-3xl font-bold mt-1" :class="marginData.summary.avg_margin >= 30 ? 'text-green-600' : (marginData.summary.avg_margin >= 15 ? 'text-yellow-600' : 'text-red-600')"
                   x-text="marginData.summary.avg_margin + '%'"></p>
            </div>
        </div>

        {{-- Предупреждение о товарах без себестоимости --}}
        <div x-show="marginData.summary.products_without_cost > 0"
             class="bg-yellow-50 border border-yellow-200 rounded-xl px-6 py-4 flex items-center space-x-3">
            <svg class="w-6 h-6 text-yellow-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-yellow-800">
                    У <span x-text="marginData.summary.products_without_cost" class="font-bold"></span> товаров не указана себестоимость
                </p>
                <p class="text-xs text-yellow-600 mt-0.5">Укажите закупочную цену в карточке товара для точного расчёта маржи</p>
            </div>
        </div>

        {{-- Графики --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Топ-10 по маржинальности</h3>
                <div class="relative" style="height: 320px;">
                    <canvas id="marginBarChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Структура: Выручка / Себестоимость / Прибыль</h3>
                <div class="relative" style="height: 320px;">
                    <canvas id="marginProfitChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Таблица --}}
        <div x-show="marginData.products.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Рейтинг товаров по маржинальности</h3>
                <p class="text-sm text-gray-500" x-text="'Всего: ' + marginData.products.length + ' товаров'"></p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Товар</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">SKU</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Кол-во</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Выручка</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Себест.</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Прибыль</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Маржа</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(product, index) in getMarginPagedProducts()" :key="index">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold"
                                          :class="product.rank <= 3 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                          x-text="product.rank"></span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 max-w-[200px] truncate" x-text="product.name"></td>
                                <td class="px-4 py-3 text-sm text-gray-500 font-mono text-xs" x-text="product.sku"></td>
                                <td class="px-4 py-3 text-right text-sm text-gray-600" x-text="product.quantity"></td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-gray-900" x-text="formatMoney(product.revenue)"></td>
                                <td class="px-4 py-3 text-right text-sm" :class="product.has_cost ? 'text-gray-600' : 'text-gray-300'"
                                    x-text="product.has_cost ? formatMoney(product.cost) : '—'"></td>
                                <td class="px-4 py-3 text-right text-sm font-medium"
                                    :class="product.has_cost ? (product.profit >= 0 ? 'text-green-600' : 'text-red-600') : 'text-gray-300'"
                                    x-text="product.has_cost ? formatMoney(product.profit) : '—'"></td>
                                <td class="px-4 py-3 text-right">
                                    <template x-if="product.has_cost">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold text-white"
                                              :class="product.margin_percent >= 30 ? 'bg-green-500' : (product.margin_percent >= 15 ? 'bg-yellow-500' : 'bg-red-500')"
                                              x-text="product.margin_percent + '%'"></span>
                                    </template>
                                    <template x-if="!product.has_cost">
                                        <span class="text-sm text-gray-300">—</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Пагинация --}}
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Показано <span x-text="Math.min((marginPage - 1) * marginPerPage + 1, marginData.products.length)"></span>–<span x-text="Math.min(marginPage * marginPerPage, marginData.products.length)"></span>
                    из <span x-text="marginData.products.length"></span>
                </div>
                <div class="flex items-center space-x-1">
                    <button @click="marginPage = 1" :disabled="marginPage === 1"
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">&laquo;</button>
                    <button @click="marginPage = Math.max(1, marginPage - 1)" :disabled="marginPage === 1"
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">&lsaquo;</button>
                    <span class="px-3 py-1.5 text-sm font-medium"><span x-text="marginPage"></span> / <span x-text="marginTotalPages()"></span></span>
                    <button @click="marginPage = Math.min(marginTotalPages(), marginPage + 1)" :disabled="marginPage >= marginTotalPages()"
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">&rsaquo;</button>
                    <button @click="marginPage = marginTotalPages()" :disabled="marginPage >= marginTotalPages()"
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">&raquo;</button>
                    <select x-model.number="marginPerPage" @change="marginPage = 1"
                            class="ml-2 px-2 py-1.5 text-sm border border-gray-300 rounded-lg">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Пусто --}}
        <div x-show="marginData.products.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <p class="text-gray-500">Нет данных о маржинальности за выбранный период</p>
        </div>
    </div>
</div>
