{{-- SWOT Анализ --}}
<div>
    <div class="space-y-6">
        {{-- Заголовок + кнопка сохранения --}}
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">SWOT-анализ вашего бизнеса</h3>
                <p class="text-sm text-gray-500">Оцените сильные и слабые стороны, возможности и угрозы</p>
            </div>
            <button @click="saveSwot()" :disabled="swotSaving"
                    class="px-5 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 transition flex items-center space-x-2">
                <template x-if="swotSaving">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </template>
                <span x-text="swotSaving ? 'Сохранение...' : 'Сохранить'"></span>
            </button>
        </div>

        {{-- SWOT матрица --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Strengths / Кучли томонлар --}}
            <div class="bg-white rounded-xl shadow-sm border-2 border-green-200 overflow-hidden">
                <div class="bg-green-50 px-5 py-3 border-b border-green-200">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-green-800">S — Strengths</h4>
                            <p class="text-xs text-green-600">Kuchli tomonlar</p>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="space-y-2 mb-3">
                        <template x-for="(item, index) in swot.strengths" :key="'s-'+index">
                            <div class="flex items-center justify-between bg-green-50 rounded-lg px-3 py-2 group">
                                <span class="text-sm text-gray-800" x-text="item"></span>
                                <button @click="removeSwotItem('strengths', index)"
                                        class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition ml-2 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div class="flex space-x-2">
                        <input type="text" x-model="newItem.strengths"
                               @keydown.enter="addSwotItem('strengths')"
                               placeholder="Добавить сильную сторону..."
                               class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <button @click="addSwotItem('strengths')"
                                class="px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm">
                            +
                        </button>
                    </div>
                </div>
            </div>

            {{-- Weaknesses / Заиф томонлар --}}
            <div class="bg-white rounded-xl shadow-sm border-2 border-red-200 overflow-hidden">
                <div class="bg-red-50 px-5 py-3 border-b border-red-200">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-red-800">W — Weaknesses</h4>
                            <p class="text-xs text-red-600">Zaif tomonlar</p>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="space-y-2 mb-3">
                        <template x-for="(item, index) in swot.weaknesses" :key="'w-'+index">
                            <div class="flex items-center justify-between bg-red-50 rounded-lg px-3 py-2 group">
                                <span class="text-sm text-gray-800" x-text="item"></span>
                                <button @click="removeSwotItem('weaknesses', index)"
                                        class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition ml-2 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div class="flex space-x-2">
                        <input type="text" x-model="newItem.weaknesses"
                               @keydown.enter="addSwotItem('weaknesses')"
                               placeholder="Добавить слабую сторону..."
                               class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        <button @click="addSwotItem('weaknesses')"
                                class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-sm">
                            +
                        </button>
                    </div>
                </div>
            </div>

            {{-- Opportunities / Имкониятлар --}}
            <div class="bg-white rounded-xl shadow-sm border-2 border-blue-200 overflow-hidden">
                <div class="bg-blue-50 px-5 py-3 border-b border-blue-200">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-blue-800">O — Opportunities</h4>
                            <p class="text-xs text-blue-600">Imkoniyatlar</p>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="space-y-2 mb-3">
                        <template x-for="(item, index) in swot.opportunities" :key="'o-'+index">
                            <div class="flex items-center justify-between bg-blue-50 rounded-lg px-3 py-2 group">
                                <span class="text-sm text-gray-800" x-text="item"></span>
                                <button @click="removeSwotItem('opportunities', index)"
                                        class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition ml-2 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div class="flex space-x-2">
                        <input type="text" x-model="newItem.opportunities"
                               @keydown.enter="addSwotItem('opportunities')"
                               placeholder="Добавить возможность..."
                               class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <button @click="addSwotItem('opportunities')"
                                class="px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-sm">
                            +
                        </button>
                    </div>
                </div>
            </div>

            {{-- Threats / Тахдидлар --}}
            <div class="bg-white rounded-xl shadow-sm border-2 border-orange-200 overflow-hidden">
                <div class="bg-orange-50 px-5 py-3 border-b border-orange-200">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-orange-800">T — Threats</h4>
                            <p class="text-xs text-orange-600">Tahdidlar</p>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="space-y-2 mb-3">
                        <template x-for="(item, index) in swot.threats" :key="'t-'+index">
                            <div class="flex items-center justify-between bg-orange-50 rounded-lg px-3 py-2 group">
                                <span class="text-sm text-gray-800" x-text="item"></span>
                                <button @click="removeSwotItem('threats', index)"
                                        class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition ml-2 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div class="flex space-x-2">
                        <input type="text" x-model="newItem.threats"
                               @keydown.enter="addSwotItem('threats')"
                               placeholder="Добавить угрозу..."
                               class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                        <button @click="addSwotItem('threats')"
                                class="px-3 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition text-sm">
                            +
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Подсказка --}}
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Как заполнять SWOT-анализ</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-gray-600">
                <div>
                    <p><strong class="text-green-600">S (Strengths):</strong> Что вы делаете лучше конкурентов? Уникальные ресурсы, навыки, репутация.</p>
                </div>
                <div>
                    <p><strong class="text-red-600">W (Weaknesses):</strong> Где вы уступаете? Нехватка ресурсов, слабые процессы, проблемы с качеством.</p>
                </div>
                <div>
                    <p><strong class="text-blue-600">O (Opportunities):</strong> Какие тренды можно использовать? Новые рынки, технологии, партнёрства.</p>
                </div>
                <div>
                    <p><strong class="text-orange-600">T (Threats):</strong> Что может навредить? Конкуренция, изменения рынка, регулирование.</p>
                </div>
            </div>
        </div>
    </div>
</div>
