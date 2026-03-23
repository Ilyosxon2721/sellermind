{{--
    AI SEO Modal — переиспользуется на всех страницах товаров маркетплейса.
    Требует в Alpine.js компоненте:
      данные: seoModalOpen, seoLoading, seoResult, seoLanguage, seocopied,
              titleApplied, titleApplying, seoHistory, seoBothLoading, seoResultBoth, seoBothMode
      методы: runSeoOptimize(), runSeoBoth(), copySeoField(), applyTitle()
      переменная: selectedProductForSeo (объект с .id и .title)
--}}
<div x-show="seoModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col" @click.outside="seoModalOpen = false">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-br from-violet-600 to-purple-600 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">AI SEO оптимизация</h3>
                    <p class="text-xs text-gray-500" x-text="(selectedProductForSeo?.title || '').substring(0, 60) + ((selectedProductForSeo?.title || '').length > 60 ? '...' : '')"></p>
                </div>
            </div>
            <button @click="seoModalOpen = false" class="text-gray-400 hover:text-gray-600 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Controls --}}
        <div class="px-6 py-3 border-b border-gray-100 flex items-center space-x-3 flex-wrap gap-2">
            <span class="text-sm text-gray-600 font-medium">Язык:</span>
            <div class="flex rounded-lg border border-gray-200 overflow-hidden">
                <button @click="seoLanguage = 'ru'"
                        class="px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="seoLanguage === 'ru' ? 'bg-purple-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'">
                    Русский
                </button>
                <button @click="seoLanguage = 'uz'"
                        class="px-3 py-1.5 text-sm font-medium transition-colors border-l border-gray-200"
                        :class="seoLanguage === 'uz' ? 'bg-purple-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'">
                    O'zbek
                </button>
            </div>
            <div class="ml-auto flex items-center space-x-2">
                <button @click="runSeoBoth()"
                        :disabled="seoBothLoading || seoLoading"
                        class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium border border-purple-300 text-purple-700 hover:bg-purple-50 transition-all"
                        :class="(seoBothLoading || seoLoading) ? 'opacity-50 cursor-not-allowed' : ''">
                    <svg class="w-4 h-4 mr-1.5" :class="seoBothLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                    <span x-text="seoBothLoading ? 'Генерация...' : 'RU + UZ'"></span>
                </button>
                <button @click="runSeoOptimize()"
                        :disabled="seoLoading"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-all"
                        :class="seoLoading ? 'bg-purple-100 text-purple-400 cursor-not-allowed' : 'bg-purple-600 text-white hover:bg-purple-700 shadow-sm'">
                    <svg class="w-4 h-4 mr-2" :class="seoLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="seoLoading ? 'Генерация...' : (seoResult ? 'Сгенерировать снова' : 'Сгенерировать')"></span>
                </button>
            </div>
        </div>

        {{-- Content --}}
        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">

            {{-- Empty state --}}
            <div x-show="!seoLoading && !seoBothLoading && !seoResult" class="text-center py-12">
                <div class="w-16 h-16 bg-purple-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <p class="text-gray-500 text-sm">Нажмите «Сгенерировать», чтобы AI создал SEO-оптимизированные тексты для карточки товара</p>
            </div>

            {{-- Loading skeleton --}}
            <div x-show="seoLoading || seoBothLoading" class="space-y-4 animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                <div class="h-10 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded w-1/3 mt-4"></div>
                <div class="h-20 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded w-1/4 mt-4"></div>
                <div class="space-y-2">
                    <div class="h-6 bg-gray-200 rounded w-4/5"></div>
                    <div class="h-6 bg-gray-200 rounded w-3/5"></div>
                    <div class="h-6 bg-gray-200 rounded w-4/5"></div>
                </div>
            </div>

            {{-- Language tabs (both mode) --}}
            <div x-show="seoBothMode && !seoLoading && !seoBothLoading && (seoResultBoth.ru || seoResultBoth.uz)"
                 class="flex rounded-lg border border-gray-200 overflow-hidden">
                <button @click="seoResult = seoResultBoth.ru; seoLanguage = 'ru'; titleApplied = false"
                        class="flex-1 px-3 py-2 text-sm font-medium transition-colors"
                        :class="seoLanguage === 'ru' ? 'bg-purple-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'">
                    RU Русский
                </button>
                <button @click="seoResult = seoResultBoth.uz; seoLanguage = 'uz'; titleApplied = false"
                        class="flex-1 px-3 py-2 text-sm font-medium transition-colors border-l border-gray-200"
                        :class="seoLanguage === 'uz' ? 'bg-purple-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'">
                    UZ O'zbek
                </button>
            </div>

            {{-- Results --}}
            <template x-if="!seoLoading && !seoBothLoading && seoResult">
                <div class="space-y-4">
                    {{-- Title --}}
                    <div x-show="seoResult.title">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Название</label>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-medium"
                                      :class="(seoResult.title || '').length > 100 ? 'text-red-500' : 'text-gray-400'"
                                      x-text="(seoResult.title || '').length + '/100 симв.'"></span>
                                <button @click="copySeoField(seoResult.title, 'title')"
                                        class="text-xs text-purple-600 hover:text-purple-800 flex items-center space-x-1">
                                    <svg x-show="seocopied !== 'title'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                    <svg x-show="seocopied === 'title'" x-transition.opacity class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="seocopied === 'title' ? 'Скопировано!' : 'Копировать'"></span>
                                </button>
                                <button @click="applyTitle()" :disabled="titleApplying"
                                        class="text-xs text-green-600 hover:text-green-800 flex items-center space-x-1 ml-1">
                                    <svg x-show="!titleApplied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <svg x-show="titleApplied" x-transition.opacity class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span x-text="titleApplying ? 'Применяю...' : (titleApplied ? 'Применено!' : 'Применить')"></span>
                                </button>
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-xl px-4 py-3 text-sm text-gray-900 font-medium" x-text="seoResult.title"></div>
                    </div>

                    {{-- Short description --}}
                    <div x-show="seoResult.short_description">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Краткое описание</label>
                            <button @click="copySeoField(seoResult.short_description, 'short')"
                                    class="text-xs text-purple-600 hover:text-purple-800 flex items-center space-x-1">
                                <svg x-show="seocopied !== 'short'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                <svg x-show="seocopied === 'short'" x-transition.opacity class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span x-text="seocopied === 'short' ? 'Скопировано!' : 'Копировать'"></span>
                            </button>
                        </div>
                        <div class="bg-gray-50 rounded-xl px-4 py-3 text-sm text-gray-700" x-text="seoResult.short_description"></div>
                    </div>

                    {{-- Full description --}}
                    <div x-show="seoResult.full_description">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Полное описание</label>
                            <button @click="copySeoField(seoResult.full_description, 'full')"
                                    class="text-xs text-purple-600 hover:text-purple-800 flex items-center space-x-1">
                                <svg x-show="seocopied !== 'full'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                <svg x-show="seocopied === 'full'" x-transition.opacity class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span x-text="seocopied === 'full' ? 'Скопировано!' : 'Копировать'"></span>
                            </button>
                        </div>
                        <div class="bg-gray-50 rounded-xl px-4 py-3 text-sm text-gray-700 max-h-48 overflow-y-auto" x-text="seoResult.full_description"></div>
                    </div>

                    {{-- Bullets --}}
                    <div x-show="seoResult.bullets?.length">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Преимущества</label>
                            <button @click="copySeoField((seoResult.bullets || []).join('\n'), 'bullets')"
                                    class="text-xs text-purple-600 hover:text-purple-800 flex items-center space-x-1">
                                <svg x-show="seocopied !== 'bullets'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                <svg x-show="seocopied === 'bullets'" x-transition.opacity class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span x-text="seocopied === 'bullets' ? 'Скопировано!' : 'Копировать'"></span>
                            </button>
                        </div>
                        <div class="bg-gray-50 rounded-xl px-4 py-3 space-y-1.5">
                            <template x-for="(bullet, i) in (seoResult.bullets || [])" :key="i">
                                <div class="flex items-start space-x-2 text-sm text-gray-700">
                                    <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-purple-500 flex-shrink-0"></span>
                                    <span x-text="bullet"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Keywords --}}
                    <div x-show="seoResult.keywords?.length">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ключевые слова</label>
                            <button @click="copySeoField((seoResult.keywords || []).join(', '), 'keywords')"
                                    class="text-xs text-purple-600 hover:text-purple-800 flex items-center space-x-1">
                                <svg x-show="seocopied !== 'keywords'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                <svg x-show="seocopied === 'keywords'" x-transition.opacity class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span x-text="seocopied === 'keywords' ? 'Скопировано!' : 'Копировать'"></span>
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="(kw, i) in (seoResult.keywords || [])" :key="i">
                                <span class="px-2.5 py-1 bg-purple-50 text-purple-700 text-xs rounded-full font-medium border border-purple-100" x-text="kw"></span>
                            </template>
                        </div>
                    </div>

                    {{-- Attributes --}}
                    <div x-show="seoResult.attributes && Object.keys(seoResult.attributes || {}).length > 0">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1.5">Характеристики</label>
                        <div class="bg-gray-50 rounded-xl divide-y divide-gray-200 text-sm">
                            <template x-for="[key, val] in Object.entries(seoResult.attributes || {})" :key="key">
                                <div class="flex justify-between px-4 py-2.5">
                                    <span class="text-gray-500" x-text="key"></span>
                                    <span class="font-medium text-gray-900" x-text="val"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- History --}}
                    <div x-show="seoHistory.length > 1" class="border-t border-gray-100 pt-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">История генераций</p>
                        <div class="space-y-1.5">
                            <template x-for="(h, i) in seoHistory.slice(1)" :key="i">
                                <button @click="seoResult = h.result; seoLanguage = h.language; titleApplied = false; seoBothMode = false"
                                        class="w-full text-left px-3 py-2 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors text-xs">
                                    <span class="font-medium text-gray-700" x-text="h.language === 'uz' ? 'UZ O\'zbek' : 'RU Русский'"></span>
                                    <span class="text-gray-400 ml-2" x-text="new Date(h.ts).toLocaleTimeString('ru-RU', {hour:'2-digit', minute:'2-digit'})"></span>
                                    <span class="block text-gray-500 mt-0.5 truncate" x-text="h.result?.title || ''"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
