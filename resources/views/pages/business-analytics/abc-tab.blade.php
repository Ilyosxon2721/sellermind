{{-- ABC Анализ товаров --}}
<div>
    {{-- Загрузка --}}
    <div x-show="loading" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl p-6 animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-1/2 mb-3"></div>
                <div class="h-8 bg-gray-200 rounded w-3/4"></div>
            </div>
            <div class="bg-white rounded-xl p-6 animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-1/2 mb-3"></div>
                <div class="h-8 bg-gray-200 rounded w-3/4"></div>
            </div>
            <div class="bg-white rounded-xl p-6 animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-1/2 mb-3"></div>
                <div class="h-8 bg-gray-200 rounded w-3/4"></div>
            </div>
        </div>
    </div>

    <div x-show="!loading" class="space-y-6">
        {{-- Сводка --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-sm text-gray-500">Всего товаров</p>
                <p class="text-3xl font-bold text-gray-900 mt-1" x-text="abcData.summary.total_products"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-sm text-gray-500">Общая выручка</p>
                <p class="text-3xl font-bold text-gray-900 mt-1" x-text="formatMoney(abcData.summary.total_revenue) + ' сум'"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-sm text-gray-500">Период</p>
                <p class="text-3xl font-bold text-gray-900 mt-1" x-text="period === 'today' ? 'Сегодня' : period === '7days' ? '7 дней' : period === '30days' ? '30 дней' : period === '90days' ? '90 дней' : 'Год'"></p>
            </div>
        </div>

        {{-- Графики --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Pie Chart: Распределение выручки --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Распределение выручки</h3>
                <div class="relative" style="height: 280px;">
                    <canvas id="abcPieChart"></canvas>
                </div>
            </div>

            {{-- Bar Chart: Кол-во товаров и выручка --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Товары vs Выручка</h3>
                <div class="relative" style="height: 280px;">
                    <canvas id="abcBarChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Топ-10 товаров — горизонтальный bar chart --}}
        <div x-show="abcData.products.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Топ-10 товаров по выручке</h3>
            <div class="relative" style="height: 320px;">
                <canvas id="abcTopProductsChart"></canvas>
            </div>
        </div>

        {{-- ABC распределение таблица --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">ABC Распределение</h3>
                <p class="text-sm text-gray-500 mt-1">Классификация товаров по вкладу в выручку</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Категория</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">% Ассортимента</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">% Продаж</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Кол-во товаров</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Выручка</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="cat in ['A', 'B', 'C']" :key="cat">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <span class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-lg"
                                              :class="cat === 'A' ? 'bg-green-500' : (cat === 'B' ? 'bg-yellow-500' : 'bg-red-500')"
                                              x-text="cat"></span>
                                        <div>
                                            <p class="font-semibold text-gray-900"
                                               x-text="cat === 'A' ? 'Лидеры продаж' : (cat === 'B' ? 'Средний спрос' : 'Аутсайдеры')"></p>
                                            <p class="text-xs text-gray-500"
                                               x-text="cat === 'A' ? 'Основной источник дохода' : (cat === 'B' ? 'Стабильный спрос' : 'Низкий спрос')"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-2xl font-bold"
                                          :class="cat === 'A' ? 'text-green-600' : (cat === 'B' ? 'text-yellow-600' : 'text-red-600')"
                                          x-text="(abcData.summary.categories[cat]?.assortment_percentage || 0) + '%'"></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-2xl font-bold"
                                          :class="cat === 'A' ? 'text-green-600' : (cat === 'B' ? 'text-yellow-600' : 'text-red-600')"
                                          x-text="(abcData.summary.categories[cat]?.percentage || 0) + '%'"></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-lg font-semibold text-gray-900"
                                          x-text="abcData.summary.categories[cat]?.count || 0"></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-lg font-semibold text-gray-900"
                                          x-text="formatMoney(abcData.summary.categories[cat]?.revenue || 0)"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Визуальная шкала --}}
            <div class="px-6 py-4 border-t border-gray-100">
                <p class="text-sm font-medium text-gray-700 mb-2">Распределение выручки</p>
                <div class="flex h-8 rounded-lg overflow-hidden">
                    <div class="bg-green-500 flex items-center justify-center text-white text-xs font-bold transition-all duration-500"
                         :style="'width: ' + Math.max(abcData.summary.categories.A?.percentage || 0, 5) + '%'">
                        <span x-text="'A: ' + (abcData.summary.categories.A?.percentage || 0) + '%'"></span>
                    </div>
                    <div class="bg-yellow-500 flex items-center justify-center text-white text-xs font-bold transition-all duration-500"
                         :style="'width: ' + Math.max(abcData.summary.categories.B?.percentage || 0, 5) + '%'">
                        <span x-text="'B: ' + (abcData.summary.categories.B?.percentage || 0) + '%'"></span>
                    </div>
                    <div class="bg-red-500 flex items-center justify-center text-white text-xs font-bold transition-all duration-500"
                         :style="'width: ' + Math.max(abcData.summary.categories.C?.percentage || 0, 5) + '%'">
                        <span x-text="'C: ' + (abcData.summary.categories.C?.percentage || 0) + '%'"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Список товаров с пагинацией --}}
        <div x-show="abcData.products.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Товары по категориям</h3>
                    <p class="text-sm text-gray-500" x-text="'Всего: ' + abcData.products.length + ' товаров'"></p>
                </div>
                {{-- Фильтр по категории --}}
                <div class="flex items-center space-x-2">
                    <button @click="abcFilter = 'all'"
                            :class="abcFilter === 'all' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition">Все</button>
                    <button @click="abcFilter = 'A'"
                            :class="abcFilter === 'A' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition">A</button>
                    <button @click="abcFilter = 'B'"
                            :class="abcFilter === 'B' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition">B</button>
                    <button @click="abcFilter = 'C'"
                            :class="abcFilter === 'C' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition">C</button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Товар</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">SKU</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Категория</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Продано</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Выручка</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Кумул. %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(product, index) in getPagedProducts()" :key="index">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm text-gray-500" x-text="(abcPage - 1) * abcPerPage + index + 1"></td>
                                <td class="px-6 py-3 text-sm font-medium text-gray-900 max-w-xs truncate" x-text="product.product_name"></td>
                                <td class="px-6 py-3 text-sm text-gray-500 font-mono" x-text="product.sku"></td>
                                <td class="px-6 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold text-white"
                                          :class="product.category === 'A' ? 'bg-green-500' : (product.category === 'B' ? 'bg-yellow-500' : 'bg-red-500')"
                                          x-text="product.category"></span>
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-gray-900" x-text="product.quantity"></td>
                                <td class="px-6 py-3 text-right text-sm font-medium text-gray-900" x-text="formatMoney(product.revenue)"></td>
                                <td class="px-6 py-3 text-right text-sm text-gray-500" x-text="product.cumulative_percentage + '%'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Пагинация --}}
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Показано <span x-text="Math.min((abcPage - 1) * abcPerPage + 1, getFilteredProducts().length)"></span>–<span x-text="Math.min(abcPage * abcPerPage, getFilteredProducts().length)"></span>
                    из <span x-text="getFilteredProducts().length"></span>
                </div>
                <div class="flex items-center space-x-1">
                    <button @click="abcPage = 1" :disabled="abcPage === 1"
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        &laquo;
                    </button>
                    <button @click="abcPage = Math.max(1, abcPage - 1)" :disabled="abcPage === 1"
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        &lsaquo;
                    </button>
                    <span class="px-3 py-1.5 text-sm font-medium">
                        <span x-text="abcPage"></span> / <span x-text="abcTotalPages()"></span>
                    </span>
                    <button @click="abcPage = Math.min(abcTotalPages(), abcPage + 1)" :disabled="abcPage >= abcTotalPages()"
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        &rsaquo;
                    </button>
                    <button @click="abcPage = abcTotalPages()" :disabled="abcPage >= abcTotalPages()"
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        &raquo;
                    </button>
                    <select x-model.number="abcPerPage" @change="abcPage = 1"
                            class="ml-2 px-2 py-1.5 text-sm border border-gray-300 rounded-lg">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Пусто --}}
        <div x-show="abcData.products.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <p class="text-gray-500">Нет данных за выбранный период</p>
            <p class="text-xs text-gray-400 mt-1">Данные появятся после первых продаж</p>
        </div>
    </div>
</div>
