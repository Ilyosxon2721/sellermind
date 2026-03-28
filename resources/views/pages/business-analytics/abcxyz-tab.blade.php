{{-- ABCXYZ Анализ клиентов --}}
<div>
    {{-- Загрузка --}}
    <div x-show="loading" class="space-y-4">
        <div class="bg-white rounded-xl p-6 animate-pulse">
            <div class="h-4 bg-gray-200 rounded w-1/3 mb-4"></div>
            <div class="grid grid-cols-3 gap-4">
                <div class="h-24 bg-gray-200 rounded"></div>
                <div class="h-24 bg-gray-200 rounded"></div>
                <div class="h-24 bg-gray-200 rounded"></div>
                <div class="h-24 bg-gray-200 rounded"></div>
                <div class="h-24 bg-gray-200 rounded"></div>
                <div class="h-24 bg-gray-200 rounded"></div>
                <div class="h-24 bg-gray-200 rounded"></div>
                <div class="h-24 bg-gray-200 rounded"></div>
                <div class="h-24 bg-gray-200 rounded"></div>
            </div>
        </div>
    </div>

    <div x-show="!loading" class="space-y-6">
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

                {{-- Строка X --}}
                <div class="grid grid-cols-4 gap-3 mb-3">
                    <div class="flex items-center">
                        <div>
                            <div class="text-sm font-bold text-gray-700">X — Kun</div>
                            <div class="text-xs text-gray-400">Ежедневно</div>
                            <div class="text-xs text-gray-400">5+/нед</div>
                        </div>
                    </div>
                    <div class="rounded-xl p-4 text-center bg-green-600 text-white">
                        <div class="text-xs font-bold opacity-75">AX</div>
                        <div class="text-2xl font-bold mt-1" x-text="abcxyzData.matrix.AX?.count || 0"></div>
                        <div class="text-xs opacity-75 mt-1">клиентов</div>
                        <div class="text-xs font-semibold mt-1" x-text="formatMoney(abcxyzData.matrix.AX?.revenue || 0)"></div>
                    </div>
                    <div class="rounded-xl p-4 text-center bg-yellow-500 text-white">
                        <div class="text-xs font-bold opacity-75">BX</div>
                        <div class="text-2xl font-bold mt-1" x-text="abcxyzData.matrix.BX?.count || 0"></div>
                        <div class="text-xs opacity-75 mt-1">клиентов</div>
                        <div class="text-xs font-semibold mt-1" x-text="formatMoney(abcxyzData.matrix.BX?.revenue || 0)"></div>
                    </div>
                    <div class="rounded-xl p-4 text-center bg-red-400 text-white">
                        <div class="text-xs font-bold opacity-75">CX</div>
                        <div class="text-2xl font-bold mt-1" x-text="abcxyzData.matrix.CX?.count || 0"></div>
                        <div class="text-xs opacity-75 mt-1">клиентов</div>
                        <div class="text-xs font-semibold mt-1" x-text="formatMoney(abcxyzData.matrix.CX?.revenue || 0)"></div>
                    </div>
                </div>

                {{-- Строка Y --}}
                <div class="grid grid-cols-4 gap-3 mb-3">
                    <div class="flex items-center">
                        <div>
                            <div class="text-sm font-bold text-gray-700">Y — Hafta</div>
                            <div class="text-xs text-gray-400">Еженедельно</div>
                            <div class="text-xs text-gray-400">1-4/нед</div>
                        </div>
                    </div>
                    <div class="rounded-xl p-4 text-center bg-green-500 text-white">
                        <div class="text-xs font-bold opacity-75">AY</div>
                        <div class="text-2xl font-bold mt-1" x-text="abcxyzData.matrix.AY?.count || 0"></div>
                        <div class="text-xs opacity-75 mt-1">клиентов</div>
                        <div class="text-xs font-semibold mt-1" x-text="formatMoney(abcxyzData.matrix.AY?.revenue || 0)"></div>
                    </div>
                    <div class="rounded-xl p-4 text-center bg-yellow-400 text-gray-900">
                        <div class="text-xs font-bold opacity-75">BY</div>
                        <div class="text-2xl font-bold mt-1" x-text="abcxyzData.matrix.BY?.count || 0"></div>
                        <div class="text-xs opacity-75 mt-1">клиентов</div>
                        <div class="text-xs font-semibold mt-1" x-text="formatMoney(abcxyzData.matrix.BY?.revenue || 0)"></div>
                    </div>
                    <div class="rounded-xl p-4 text-center bg-red-300 text-gray-900">
                        <div class="text-xs font-bold opacity-75">CY</div>
                        <div class="text-2xl font-bold mt-1" x-text="abcxyzData.matrix.CY?.count || 0"></div>
                        <div class="text-xs opacity-75 mt-1">клиентов</div>
                        <div class="text-xs font-semibold mt-1" x-text="formatMoney(abcxyzData.matrix.CY?.revenue || 0)"></div>
                    </div>
                </div>

                {{-- Строка Z --}}
                <div class="grid grid-cols-4 gap-3 mb-3">
                    <div class="flex items-center">
                        <div>
                            <div class="text-sm font-bold text-gray-700">Z — Oy</div>
                            <div class="text-xs text-gray-400">Ежемесячно</div>
                            <div class="text-xs text-gray-400">&lt;1/нед</div>
                        </div>
                    </div>
                    <div class="rounded-xl p-4 text-center bg-green-400 text-white">
                        <div class="text-xs font-bold opacity-75">AZ</div>
                        <div class="text-2xl font-bold mt-1" x-text="abcxyzData.matrix.AZ?.count || 0"></div>
                        <div class="text-xs opacity-75 mt-1">клиентов</div>
                        <div class="text-xs font-semibold mt-1" x-text="formatMoney(abcxyzData.matrix.AZ?.revenue || 0)"></div>
                    </div>
                    <div class="rounded-xl p-4 text-center bg-yellow-300 text-gray-900">
                        <div class="text-xs font-bold opacity-75">BZ</div>
                        <div class="text-2xl font-bold mt-1" x-text="abcxyzData.matrix.BZ?.count || 0"></div>
                        <div class="text-xs opacity-75 mt-1">клиентов</div>
                        <div class="text-xs font-semibold mt-1" x-text="formatMoney(abcxyzData.matrix.BZ?.revenue || 0)"></div>
                    </div>
                    <div class="rounded-xl p-4 text-center bg-red-200 text-gray-900">
                        <div class="text-xs font-bold opacity-75">CZ</div>
                        <div class="text-2xl font-bold mt-1" x-text="abcxyzData.matrix.CZ?.count || 0"></div>
                        <div class="text-xs opacity-75 mt-1">клиентов</div>
                        <div class="text-xs font-semibold mt-1" x-text="formatMoney(abcxyzData.matrix.CZ?.revenue || 0)"></div>
                    </div>
                </div>
            </div>

            {{-- Легенда --}}
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Обозначения</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <div class="flex items-center space-x-2">
                        <span class="w-4 h-4 rounded bg-green-600"></span>
                        <span class="text-xs text-gray-600"><strong>AX</strong> — VIP ежедневные</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="w-4 h-4 rounded bg-green-500"></span>
                        <span class="text-xs text-gray-600"><strong>AY</strong> — VIP еженедельные</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="w-4 h-4 rounded bg-green-400"></span>
                        <span class="text-xs text-gray-600"><strong>AZ</strong> — VIP редкие</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="w-4 h-4 rounded bg-yellow-500"></span>
                        <span class="text-xs text-gray-600"><strong>BX</strong> — Средние ежедневные</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="w-4 h-4 rounded bg-yellow-400"></span>
                        <span class="text-xs text-gray-600"><strong>BY</strong> — Средние еженедельные</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="w-4 h-4 rounded bg-yellow-300"></span>
                        <span class="text-xs text-gray-600"><strong>BZ</strong> — Средние редкие</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="w-4 h-4 rounded bg-red-400"></span>
                        <span class="text-xs text-gray-600"><strong>CX</strong> — Малые ежедневные</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="w-4 h-4 rounded bg-red-300"></span>
                        <span class="text-xs text-gray-600"><strong>CY</strong> — Малые еженедельные</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="w-4 h-4 rounded bg-red-200"></span>
                        <span class="text-xs text-gray-600"><strong>CZ</strong> — Малые редкие</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Пусто --}}
        <div x-show="abcxyzData.summary.total_customers === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-gray-500">Нет данных о клиентах за выбранный период</p>
            <p class="text-xs text-gray-400 mt-1">Данные берутся из ручных и оффлайн продаж</p>
        </div>
    </div>
</div>
