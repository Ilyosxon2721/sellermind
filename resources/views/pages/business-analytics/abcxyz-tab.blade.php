{{-- ABCXYZ Анализ клиентов --}}
<div>
    {{-- Загрузка --}}
    <template x-if="loading && !abcxyzLoaded">
        <div class="space-y-4">
            <div class="bg-white rounded-xl p-6 animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-1/3 mb-4"></div>
                <div class="grid grid-cols-3 gap-4">
                    <template x-for="i in 9">
                        <div class="h-24 bg-gray-200 rounded"></div>
                    </template>
                </div>
            </div>
        </div>
    </template>

    <template x-if="abcxyzLoaded">
        <div class="space-y-6">
            {{-- Сводка --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Всего клиентов</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1" x-text="abcxyzData.summary.total_customers"></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Общая выручка</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1" x-text="formatMoney(abcxyzData.summary.total_revenue) + ' сум'"></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Период (недель)</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1" x-text="abcxyzData.summary.period_weeks"></p>
                </div>
            </div>

            {{-- Матрица ABCXYZ --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">ABCXYZ Матрица клиентов</h3>
                    <p class="text-sm text-gray-500 mt-1">Сегментация по сумме покупок (ABC) и частоте (XYZ)</p>
                </div>

                <div class="p-6">
                    {{-- Заголовки столбцов --}}
                    <div class="grid grid-cols-4 gap-3 mb-3">
                        <div class="flex items-end">
                            <div class="text-xs font-semibold text-gray-500 uppercase">Mijoz turi</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-green-600">A</div>
                            <div class="text-xs text-gray-500">$<span x-text="formatMoney(abcxyzData.thresholds?.A || 10000)"></span>+</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-yellow-600">B</div>
                            <div class="text-xs text-gray-500">$<span x-text="formatMoney(abcxyzData.thresholds?.B || 5000)"></span>+</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-red-600">C</div>
                            <div class="text-xs text-gray-500">&lt; $<span x-text="formatMoney(abcxyzData.thresholds?.B || 5000)"></span></div>
                        </div>
                    </div>

                    {{-- Строки матрицы --}}
                    <template x-for="row in [{xyz: 'X', label: 'Kun', desc: 'Ежедневно', freq: '5+/нед'}, {xyz: 'Y', label: 'Hafta', desc: 'Еженедельно', freq: '1-4/нед'}, {xyz: 'Z', label: 'Oy', desc: 'Ежемесячно', freq: '<1/нед'}]" :key="row.xyz">
                        <div class="grid grid-cols-4 gap-3 mb-3">
                            {{-- Метка строки --}}
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-bold text-gray-700" x-text="row.xyz + ' — ' + row.label"></div>
                                    <div class="text-xs text-gray-400" x-text="row.desc"></div>
                                    <div class="text-xs text-gray-400" x-text="row.freq"></div>
                                </div>
                            </div>

                            {{-- Ячейки --}}
                            <template x-for="abc in ['A', 'B', 'C']" :key="abc">
                                <div class="rounded-xl p-4 text-center transition-all hover:scale-105 cursor-default"
                                     :class="getSegmentColor(abc + row.xyz)">
                                    <div class="text-xs font-bold opacity-75" x-text="abc + row.xyz"></div>
                                    <div class="text-2xl font-bold mt-1" x-text="abcxyzData.matrix[abc + row.xyz]?.count || 0"></div>
                                    <div class="text-xs opacity-75 mt-1">клиентов</div>
                                    <div class="text-xs font-semibold mt-1" x-text="formatMoney(abcxyzData.matrix[abc + row.xyz]?.revenue || 0)"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Легенда --}}
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Обозначения</p>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        <template x-for="seg in ['AX', 'AY', 'AZ', 'BX', 'BY', 'BZ', 'CX', 'CY', 'CZ']" :key="seg">
                            <div class="flex items-center space-x-2">
                                <span class="w-4 h-4 rounded" :class="getSegmentColor(seg)"></span>
                                <span class="text-xs text-gray-600">
                                    <strong x-text="seg"></strong> —
                                    <span x-text="getSegmentLabel(seg)"></span>
                                </span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Детали сегментов --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Детали по сегментам</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-px bg-gray-100">
                    <template x-for="seg in ['AX', 'AY', 'AZ', 'BX', 'BY', 'BZ', 'CX', 'CY', 'CZ']" :key="seg">
                        <div class="bg-white p-4" x-show="(abcxyzData.matrix[seg]?.count || 0) > 0">
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold"
                                      :class="getSegmentColor(seg)" x-text="seg"></span>
                                <span class="text-sm text-gray-500" x-text="(abcxyzData.matrix[seg]?.count || 0) + ' клиентов'"></span>
                            </div>
                            <p class="text-xs text-gray-500 mb-2" x-text="'Выручка: ' + formatMoney(abcxyzData.matrix[seg]?.revenue || 0) + ' сум'"></p>
                            <template x-if="abcxyzData.matrix[seg]?.customers?.length > 0">
                                <div class="space-y-1">
                                    <template x-for="c in abcxyzData.matrix[seg].customers.slice(0, 3)" :key="c.name">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-700 truncate" x-text="c.name"></span>
                                            <span class="text-gray-500 ml-2 flex-shrink-0" x-text="formatMoney(c.total_amount)"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <template x-if="abcxyzData.summary.total_customers === 0">
                    <div class="px-6 py-12 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-gray-500">Нет данных о клиентах за выбранный период</p>
                        <p class="text-xs text-gray-400 mt-1">Данные берутся из ручных и оффлайн продаж</p>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
