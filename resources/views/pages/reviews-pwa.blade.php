{{--
    PWA Reviews Page
    Native-style reviews list with AI response generation, filters, and pull-to-refresh
--}}

<x-layouts.pwa :title="__('Отзывы')" :page-title="__('Отзывы')" :show-back="true">

    <x-slot name="topBar">
        <header
            class="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
            style="padding-top: var(--safe-area-inset-top);"
        >
            <div class="flex items-center justify-between px-4 h-12">
                {{-- Left: Back Button --}}
                <button
                    type="button"
                    onclick="history.back(); if(window.SmHaptic) window.SmHaptic.light();"
                    class="p-2 -ml-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                {{-- Center: Title --}}
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Отзывы
                </h1>

                {{-- Right: Actions --}}
                <div class="flex items-center space-x-2">
                    {{-- Filter Button --}}
                    <button
                        @click="$dispatch('open-filterSheet')"
                        type="button"
                        class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform relative"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        {{-- Active Filters Badge --}}
                        <span
                            x-show="activeFiltersCount > 0"
                            x-cloak
                            class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-blue-600 text-white text-xs rounded-full flex items-center justify-center"
                            x-text="activeFiltersCount"
                        ></span>
                    </button>
                </div>
            </div>
        </header>
    </x-slot>

    <x-slot name="skeleton">
        <div class="px-4 pt-3">
            {{-- Filter Chips Skeleton --}}
            <div class="flex space-x-2 mb-4 overflow-hidden">
                @for($i = 0; $i < 4; $i++)
                    <div class="skeleton h-8 w-24 rounded-full flex-shrink-0"></div>
                @endfor
            </div>

            {{-- Stats Skeleton --}}
            <div class="grid grid-cols-3 gap-3 mb-4">
                @for($i = 0; $i < 3; $i++)
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-3 shadow-sm">
                        <div class="skeleton h-3 w-12 mb-2"></div>
                        <div class="skeleton h-5 w-8"></div>
                    </div>
                @endfor
            </div>

            {{-- Review Cards Skeleton --}}
            @for($i = 0; $i < 5; $i++)
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 mb-3 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-2">
                            <div class="skeleton w-6 h-6 rounded-full"></div>
                            <div class="skeleton h-3 w-20"></div>
                        </div>
                        <div class="skeleton h-3 w-16"></div>
                    </div>
                    <div class="skeleton h-3 w-24 mb-2"></div>
                    <div class="skeleton h-4 w-full mb-1"></div>
                    <div class="skeleton h-4 w-3/4 mb-3"></div>
                    <div class="skeleton h-3 w-1/2 mb-3"></div>
                    <div class="skeleton h-9 w-full rounded-lg"></div>
                </div>
            @endfor
        </div>
    </x-slot>

    {{-- Main Content --}}
    <div
        x-data="pwaReviewsPage()"
        @pull-refresh.window="refresh()"
        class="min-h-full"
    >
        {{-- Filter Chips --}}
        <div class="px-4 pt-3">
            <div class="flex space-x-2 overflow-x-auto pb-2 scrollbar-hide">
                {{-- Marketplace Filter --}}
                <button
                    @click="setMarketplace('all')"
                    type="button"
                    class="flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium transition-colors active:scale-95"
                    :class="marketplace === 'all'
                        ? 'bg-blue-600 text-white'
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
                >
                    Все
                </button>
                <button
                    @click="setMarketplace('wildberries')"
                    type="button"
                    class="flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium transition-colors active:scale-95"
                    :class="marketplace === 'wildberries'
                        ? 'bg-purple-600 text-white'
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
                >
                    Wildberries
                </button>
                <button
                    @click="setMarketplace('ozon')"
                    type="button"
                    class="flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium transition-colors active:scale-95"
                    :class="marketplace === 'ozon'
                        ? 'bg-blue-600 text-white'
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
                >
                    Ozon
                </button>
                <button
                    @click="setMarketplace('yandex')"
                    type="button"
                    class="flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium transition-colors active:scale-95"
                    :class="marketplace === 'yandex'
                        ? 'bg-yellow-500 text-white'
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
                >
                    Yandex
                </button>

                {{-- Status chips --}}
                <div class="w-px bg-gray-200 dark:bg-gray-700 flex-shrink-0 mx-1"></div>
                <button
                    @click="setStatus('pending')"
                    type="button"
                    class="flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium transition-colors active:scale-95"
                    :class="filters.status === 'pending'
                        ? 'bg-orange-500 text-white'
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
                >
                    Без ответа
                </button>
                <button
                    @click="setStatus('responded')"
                    type="button"
                    class="flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium transition-colors active:scale-95"
                    :class="filters.status === 'responded'
                        ? 'bg-green-600 text-white'
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
                >
                    Отвечено
                </button>
            </div>
        </div>

        {{-- Rating Filter Chips --}}
        <div class="px-4 pt-1 pb-2">
            <div class="flex space-x-2 overflow-x-auto scrollbar-hide">
                <template x-for="star in [5, 4, 3, 2, 1]" :key="star">
                    <button
                        @click="setRating(star)"
                        type="button"
                        class="flex-shrink-0 flex items-center space-x-1 px-3 py-1.5 rounded-full text-sm font-medium transition-colors active:scale-95"
                        :class="filters.rating == star
                            ? 'bg-yellow-500 text-white'
                            : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
                    >
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span x-text="star"></span>
                    </button>
                </template>
                <button
                    x-show="filters.rating !== ''"
                    x-cloak
                    @click="setRating('')"
                    type="button"
                    class="flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium text-red-600 bg-red-50 dark:bg-red-900/20 active:scale-95 transition-colors"
                >
                    Сбросить
                </button>
            </div>
        </div>

        {{-- Reviews Count --}}
        <div class="px-4 py-1 flex items-center justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">
                <span x-text="totalReviews"></span> отзывов
            </span>
            <span
                x-show="stats.average_rating"
                x-cloak
                class="text-sm text-gray-600 dark:text-gray-400"
            >
                Средняя: <span class="font-medium text-yellow-600" x-text="(stats.average_rating || 0).toFixed(1)"></span>
                <svg class="w-3.5 h-3.5 inline text-yellow-500 -mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            </span>
        </div>

        {{-- Pull to Refresh Wrapper --}}
        <x-pwa.pull-to-refresh callback="refresh">
            {{-- Reviews List --}}
            <div
                x-ref="reviewsList"
                class="px-4 pb-4 space-y-3"
            >
                {{-- Empty State --}}
                <template x-if="!loading && reviews.length === 0">
                    <x-pwa.empty-state
                        :icon="'<svg class=\"w-full h-full\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z\"/></svg>'"
                        :title="'Отзывов не найдено'"
                        :description="'Попробуйте изменить фильтры или обновить страницу'"
                    >
                        <button
                            @click="resetFilters(); refresh()"
                            type="button"
                            class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 active:scale-95 transition-all"
                        >
                            Сбросить фильтры
                        </button>
                    </x-pwa.empty-state>
                </template>

                {{-- Review Cards --}}
                <template x-for="review in reviews" :key="review.id">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                        {{-- Review Header --}}
                        <div class="p-4 pb-0">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    {{-- Marketplace Icon --}}
                                    <span
                                        class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                        :class="getMarketplaceBgClass(review.marketplace)"
                                        x-text="getMarketplaceInitial(review.marketplace)"
                                    ></span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="review.customer_name || 'Аноним'"></span>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="formatDate(review.created_at)"></span>
                            </div>

                            {{-- Star Rating --}}
                            <div class="flex items-center space-x-1 mb-2">
                                <template x-for="i in 5" :key="'star-' + review.id + '-' + i">
                                    <svg
                                        class="w-4 h-4"
                                        :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-200 dark:text-gray-600'"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </template>
                                {{-- Sentiment Badge --}}
                                <span
                                    x-show="review.sentiment"
                                    x-cloak
                                    class="ml-2 px-2 py-0.5 text-xs rounded-full font-medium"
                                    :class="getSentimentBadgeClass(review.sentiment)"
                                    x-text="getSentimentLabel(review.sentiment)"
                                ></span>
                            </div>

                            {{-- Review Text --}}
                            <p
                                class="text-sm text-gray-700 dark:text-gray-300 mb-2 leading-relaxed"
                                x-text="review.review_text"
                            ></p>

                            {{-- Product Name --}}
                            <div
                                x-show="review.product"
                                x-cloak
                                class="flex items-center space-x-1.5 mb-3"
                            >
                                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <span class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="review.product?.name"></span>
                            </div>
                        </div>

                        {{-- Response Section --}}
                        <div class="border-t border-gray-100 dark:border-gray-700">
                            {{-- Existing Response --}}
                            <div
                                x-show="review.response_text && !editingReview[review.id]"
                                x-cloak
                                class="p-4 bg-blue-50 dark:bg-blue-900/20"
                            >
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-semibold text-blue-700 dark:text-blue-400">Ваш ответ</span>
                                    <div class="flex items-center space-x-2">
                                        <span
                                            x-show="review.is_ai_generated"
                                            x-cloak
                                            class="px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-xs rounded font-medium"
                                        >AI</span>
                                        <button
                                            @click="startEditingResponse(review.id, review.response_text)"
                                            type="button"
                                            class="text-xs text-blue-600 dark:text-blue-400 font-medium"
                                        >
                                            Изменить
                                        </button>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed" x-text="review.response_text"></p>
                            </div>

                            {{-- Editing Response --}}
                            <div
                                x-show="editingReview[review.id]"
                                x-cloak
                                class="p-4"
                            >
                                <textarea
                                    x-model="editingResponse[review.id]"
                                    rows="3"
                                    placeholder="Введите ваш ответ..."
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                ></textarea>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center space-x-2">
                                        <button
                                            @click="saveResponse(review.id)"
                                            :disabled="!editingResponse[review.id]"
                                            type="button"
                                            class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 active:scale-95 transition-all disabled:opacity-50"
                                        >
                                            Сохранить
                                        </button>
                                        <button
                                            @click="cancelEditing(review.id)"
                                            type="button"
                                            class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg active:scale-95 transition-all"
                                        >
                                            Отмена
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Generate Button (no response yet and not editing) --}}
                            <div
                                x-show="!review.response_text && !editingReview[review.id]"
                                class="p-3 flex items-center space-x-2"
                            >
                                <button
                                    @click="generateResponse(review.id)"
                                    :disabled="generatingResponse === review.id"
                                    type="button"
                                    class="flex-1 flex items-center justify-center space-x-2 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 active:scale-[0.98] transition-all disabled:opacity-50"
                                >
                                    <template x-if="generatingResponse !== review.id">
                                        <span class="flex items-center space-x-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                            <span>AI Ответ</span>
                                        </span>
                                    </template>
                                    <template x-if="generatingResponse === review.id">
                                        <span class="flex items-center space-x-1.5">
                                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span>Генерация...</span>
                                        </span>
                                    </template>
                                </button>
                                <button
                                    @click="startEditingResponse(review.id, '')"
                                    type="button"
                                    class="p-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg active:scale-95 transition-all"
                                    title="Написать вручную"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Loading More Indicator --}}
                <div
                    x-show="loadingMore"
                    x-cloak
                    class="flex items-center justify-center py-4"
                >
                    <svg class="w-6 h-6 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                {{-- End of List --}}
                <div
                    x-show="!hasMore && reviews.length > 0"
                    x-cloak
                    class="text-center py-4 text-sm text-gray-500 dark:text-gray-400"
                >
                    Все отзывы загружены
                </div>

                {{-- Intersection Observer Target --}}
                <div x-ref="loadMoreTrigger" class="h-1"></div>
            </div>
        </x-pwa.pull-to-refresh>

        {{-- Filter Sheet --}}
        <x-pwa.filter-sheet id="filterSheet" title="Фильтры отзывов">
            <div class="space-y-6">
                {{-- Status --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-3">
                        Статус
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="reviewStatus"
                                value=""
                                x-model="filters.status"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Все</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="reviewStatus"
                                value="pending"
                                x-model="filters.status"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Ожидают ответа</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="reviewStatus"
                                value="responded"
                                x-model="filters.status"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Отвечено</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="reviewStatus"
                                value="ignored"
                                x-model="filters.status"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Игнорируются</span>
                        </label>
                    </div>
                </div>

                {{-- Sentiment --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-3">
                        Настроение
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="reviewSentiment"
                                value=""
                                x-model="filters.sentiment"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Все</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="reviewSentiment"
                                value="positive"
                                x-model="filters.sentiment"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Позитивные</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="reviewSentiment"
                                value="neutral"
                                x-model="filters.sentiment"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Нейтральные</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="reviewSentiment"
                                value="negative"
                                x-model="filters.sentiment"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Негативные</span>
                        </label>
                    </div>
                </div>

                {{-- Rating --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-3">
                        Оценка
                    </label>
                    <select
                        x-model="filters.rating"
                        class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">Все оценки</option>
                        <option value="5">5 звезд</option>
                        <option value="4">4 звезды</option>
                        <option value="3">3 звезды</option>
                        <option value="2">2 звезды</option>
                        <option value="1">1 звезда</option>
                    </select>
                </div>

                {{-- Sort --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-3">
                        Сортировка
                    </label>
                    <select
                        x-model="filters.sortBy"
                        class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="created_desc">Новые первыми</option>
                        <option value="created_asc">Старые первыми</option>
                        <option value="rating_desc">По оценке (убыв.)</option>
                        <option value="rating_asc">По оценке (возр.)</option>
                    </select>
                </div>
            </div>

            <x-slot name="reset">
                <button
                    @click="resetFilters()"
                    type="button"
                    class="text-sm font-medium text-blue-600 dark:text-blue-400"
                >
                    Сбросить
                </button>
            </x-slot>

            <x-slot name="footer">
                <button
                    @click="applyFilters(); $dispatch('close-filterSheet')"
                    type="button"
                    class="w-full py-3 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 active:scale-[0.98] transition-all"
                >
                    Применить
                </button>
            </x-slot>
        </x-pwa.filter-sheet>
    </div>

</x-layouts.pwa>

@push('scripts')
<script>
    function pwaReviewsPage() {
        return {
            // State
            reviews: [],
            loading: false,
            loadingMore: false,
            totalReviews: 0,
            page: 1,
            perPage: 20,
            hasMore: true,
            marketplace: 'all',
            stats: {},

            // Response editing
            generatingResponse: null,
            editingReview: {},
            editingResponse: {},
            isAiGenerated: {},

            // Filters
            filters: {
                status: 'pending',
                rating: '',
                sentiment: '',
                sortBy: 'created_desc',
            },
            activeFiltersCount: 0,

            // Init
            init() {
                if (!this.$store.auth?.isAuthenticated) {
                    window.location.href = '/login';
                    return;
                }

                this.loadReviews();
                this.loadStats();
                this.setupInfiniteScroll();

                this.$watch('marketplace', () => {
                    this.page = 1;
                    this.loadReviews();
                });
            },

            // Load reviews
            async loadReviews() {
                this.loading = true;

                try {
                    const params = new URLSearchParams({
                        page: this.page,
                        per_page: this.perPage,
                    });

                    if (this.marketplace !== 'all') params.append('marketplace', this.marketplace);
                    if (this.filters.status) params.append('status', this.filters.status);
                    if (this.filters.rating) params.append('rating', this.filters.rating);
                    if (this.filters.sentiment) params.append('sentiment', this.filters.sentiment);
                    if (this.filters.sortBy) params.append('sort', this.filters.sortBy);

                    const response = await window.api.get(`/reviews?${params}`);
                    const data = response.data;

                    if (this.page === 1) {
                        this.reviews = data.data || [];
                    } else {
                        this.reviews = [...this.reviews, ...(data.data || [])];
                    }

                    this.totalReviews = data.meta?.total || data.total || this.reviews.length;
                    this.hasMore = (data.meta?.current_page || data.current_page || 1) < (data.meta?.last_page || data.last_page || 1);

                } catch (error) {
                    console.error('Failed to load reviews:', error);
                    this.showToast('Ошибка загрузки отзывов', 'error');
                } finally {
                    this.loading = false;
                    this.loadingMore = false;
                }
            },

            // Load stats
            async loadStats() {
                try {
                    const response = await window.api.get('/reviews/statistics');
                    this.stats = response.data || response || {};
                } catch (error) {
                    console.error('Failed to load stats:', error);
                }
            },

            // Load more reviews (infinite scroll)
            async loadMore() {
                if (this.loadingMore || !this.hasMore) return;

                this.loadingMore = true;
                this.page++;
                await this.loadReviews();
            },

            // Setup infinite scroll observer
            setupInfiniteScroll() {
                const observer = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting && !this.loading) {
                        this.loadMore();
                    }
                }, {
                    rootMargin: '100px',
                });

                this.$nextTick(() => {
                    if (this.$refs.loadMoreTrigger) {
                        observer.observe(this.$refs.loadMoreTrigger);
                    }
                });
            },

            // Refresh (pull-to-refresh)
            async refresh() {
                this.page = 1;
                this.hasMore = true;
                await Promise.all([
                    this.loadReviews(),
                    this.loadStats(),
                ]);
                this.triggerHaptic();
            },

            // Filter methods
            setMarketplace(value) {
                this.triggerHaptic();
                this.marketplace = value;
            },

            setStatus(value) {
                this.triggerHaptic();
                if (this.filters.status === value) {
                    this.filters.status = '';
                } else {
                    this.filters.status = value;
                }
                this.page = 1;
                this.loadReviews();
            },

            setRating(value) {
                this.triggerHaptic();
                if (this.filters.rating == value) {
                    this.filters.rating = '';
                } else {
                    this.filters.rating = value;
                }
                this.page = 1;
                this.loadReviews();
            },

            applyFilters() {
                this.updateActiveFiltersCount();
                this.page = 1;
                this.loadReviews();
            },

            resetFilters() {
                this.filters = {
                    status: '',
                    rating: '',
                    sentiment: '',
                    sortBy: 'created_desc',
                };
                this.marketplace = 'all';
                this.updateActiveFiltersCount();
            },

            updateActiveFiltersCount() {
                let count = 0;
                if (this.filters.status) count++;
                if (this.filters.rating) count++;
                if (this.filters.sentiment) count++;
                if (this.filters.sortBy !== 'created_desc') count++;
                this.activeFiltersCount = count;
            },

            // AI Response Generation
            async generateResponse(reviewId) {
                this.generatingResponse = reviewId;
                this.triggerHaptic();

                try {
                    const response = await window.api.post(`/reviews/${reviewId}/generate`, {
                        tone: 'professional',
                        length: 'medium',
                        language: 'ru',
                    });

                    const data = response.data || response;

                    if (data.response) {
                        this.editingReview[reviewId] = true;
                        this.editingResponse[reviewId] = data.response;
                        this.isAiGenerated[reviewId] = true;
                        this.showToast('Ответ сгенерирован', 'success');
                    }
                } catch (error) {
                    console.error('Failed to generate response:', error);
                    this.showToast('Ошибка генерации ответа', 'error');
                } finally {
                    this.generatingResponse = null;
                }
            },

            // Save response
            async saveResponse(reviewId) {
                try {
                    const response = await window.api.post(`/reviews/${reviewId}/save-response`, {
                        response_text: this.editingResponse[reviewId],
                        is_ai_generated: this.isAiGenerated[reviewId] || false,
                    });

                    const data = response.data || response;

                    // Update review in list
                    const reviewIndex = this.reviews.findIndex(r => r.id === reviewId);
                    if (reviewIndex !== -1) {
                        if (data.data) {
                            this.reviews[reviewIndex] = data.data;
                        } else {
                            this.reviews[reviewIndex].response_text = this.editingResponse[reviewId];
                            this.reviews[reviewIndex].is_ai_generated = this.isAiGenerated[reviewId] || false;
                        }
                    }

                    this.editingReview[reviewId] = false;
                    delete this.editingResponse[reviewId];
                    delete this.isAiGenerated[reviewId];

                    this.showToast('Ответ сохранен', 'success');
                    this.triggerHaptic();
                    await this.loadStats();
                } catch (error) {
                    console.error('Failed to save response:', error);
                    this.showToast('Ошибка сохранения ответа', 'error');
                }
            },

            startEditingResponse(reviewId, existingResponse = '') {
                this.editingReview[reviewId] = true;
                this.editingResponse[reviewId] = existingResponse;
                this.isAiGenerated[reviewId] = false;
            },

            cancelEditing(reviewId) {
                this.editingReview[reviewId] = false;
                delete this.editingResponse[reviewId];
                delete this.isAiGenerated[reviewId];
            },

            // Marketplace helpers
            getMarketplaceBgClass(marketplace) {
                const classes = {
                    wildberries: 'bg-purple-600',
                    ozon: 'bg-blue-600',
                    yandex: 'bg-yellow-500',
                    uzum: 'bg-green-600',
                };
                return classes[marketplace] || 'bg-gray-500';
            },

            getMarketplaceInitial(marketplace) {
                const initials = {
                    wildberries: 'W',
                    ozon: 'O',
                    yandex: 'Y',
                    uzum: 'U',
                };
                return initials[marketplace] || '?';
            },

            getSentimentBadgeClass(sentiment) {
                const classes = {
                    positive: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                    neutral: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                    negative: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                };
                return classes[sentiment] || 'bg-gray-100 text-gray-700';
            },

            getSentimentLabel(sentiment) {
                const labels = {
                    positive: 'Позитивный',
                    neutral: 'Нейтральный',
                    negative: 'Негативный',
                };
                return labels[sentiment] || sentiment;
            },

            // Helpers
            formatDate(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                const now = new Date();
                const diffMs = now - date;
                const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

                if (diffHours < 1) return 'Только что';
                if (diffHours < 24) return `${diffHours}ч назад`;
                if (diffDays < 7) return `${diffDays}д назад`;

                return date.toLocaleDateString('ru-RU', {
                    day: 'numeric',
                    month: 'short',
                });
            },

            triggerHaptic() {
                if (window.SmHaptic) {
                    window.SmHaptic.light();
                } else if (navigator.vibrate) {
                    navigator.vibrate(10);
                }
            },

            showToast(message, type = 'info') {
                if (window.showToast) {
                    window.showToast(message, type);
                } else {
                    alert(message);
                }
            },
        };
    }
</script>
@endpush
