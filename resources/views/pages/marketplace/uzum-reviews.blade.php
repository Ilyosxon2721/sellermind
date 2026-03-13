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
    .uzum-btn { background: linear-gradient(135deg, #3A007D 0%, #F4488D 100%); color: white; }
    .uzum-btn:hover { filter: brightness(1.1); }
    .uzum-btn:disabled { opacity: 0.6; cursor: not-allowed; filter: none; }
    .uzum-btn-outline { border: 2px solid #3A007D; color: #3A007D; }
    .uzum-btn-outline:hover { background-color: #3A007D; color: white; }
    .uzum-tab-active {
        border-bottom: 2px solid #3A007D;
        color: #3A007D;
        font-weight: 600;
    }
    .uzum-tab {
        border-bottom: 2px solid transparent;
        color: #6B7280;
    }
    .uzum-tab:hover { color: #3A007D; }
    .star-filled { color: #F4488D; }
    .star-empty { color: #D1D5DB; }
    .review-card {
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        transition: all 0.2s ease;
    }
    .review-card:hover {
        border-color: rgba(58, 0, 125, 0.3);
        box-shadow: 0 4px 20px rgba(58, 0, 125, 0.08);
    }
    .badge-unanswered {
        background: rgba(244, 72, 141, 0.1);
        color: #F4488D;
    }
    .badge-answered {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }
    .ai-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .ai-btn:hover { filter: brightness(1.1); }
    .ai-btn:disabled { opacity: 0.6; cursor: not-allowed; filter: none; }
</style>

<div x-data="uzumReviewsPage()" x-init="init()" x-cloak class="flex h-screen bg-gray-50 browser-only"
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
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-gray-900">Отзывы</h1>
                                <p class="text-sm text-gray-500">{{ $accountName ?? 'Uzum Market' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats + Refresh (only when authenticated) -->
                    <div x-show="authenticated" class="flex items-center space-x-3">
                        <!-- Summary Stats -->
                        <div x-show="!loading && stats.total > 0" class="hidden md:flex items-center space-x-4 bg-gray-50 px-4 py-2 rounded-xl text-sm">
                            <div class="flex items-center space-x-1">
                                <svg class="w-4 h-4 star-filled" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                                <span class="font-semibold text-gray-900" x-text="stats.avgRating"></span>
                                <span class="text-gray-500">средний</span>
                            </div>
                            <div class="w-px h-4 bg-gray-300"></div>
                            <span class="text-gray-600">
                                <span class="font-semibold text-gray-900" x-text="stats.unanswered"></span> без ответа
                            </span>
                        </div>

                        <!-- Refresh Button -->
                        <button @click="loadReviews()"
                                :disabled="loading"
                                class="px-4 py-2 bg-white border-2 border-[#3A007D] text-[#3A007D] hover:bg-[#3A007D] hover:text-white rounded-xl font-medium transition flex items-center space-x-2 disabled:opacity-50">
                            <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span x-text="loading ? 'Загрузка...' : 'Обновить'"></span>
                        </button>

                        <!-- Disconnect -->
                        <button @click="disconnect()" class="text-sm text-gray-400 hover:text-red-500 transition" title="Отключить аккаунт Uzum">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs (only when authenticated) -->
            <div x-show="authenticated" class="px-6 flex items-center space-x-1 border-t border-gray-100 bg-gray-50/50">
                <button @click="setFilter('unanswered')"
                        class="px-5 py-3.5 text-sm font-medium border-b-2 transition whitespace-nowrap"
                        :class="filter === 'unanswered' ? 'uzum-tab-active' : 'uzum-tab'">
                    Неотвеченные
                    <span x-show="stats.unanswered > 0"
                          class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full badge-unanswered"
                          x-text="stats.unanswered"></span>
                </button>
                <button @click="setFilter('all')"
                        class="px-5 py-3.5 text-sm font-medium border-b-2 transition whitespace-nowrap"
                        :class="filter === 'all' ? 'uzum-tab-active' : 'uzum-tab'">
                    Все
                    <span x-show="stats.total > 0"
                          class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600"
                          x-text="stats.total"></span>
                </button>
                <button @click="setFilter('answered')"
                        class="px-5 py-3.5 text-sm font-medium border-b-2 transition whitespace-nowrap"
                        :class="filter === 'answered' ? 'uzum-tab-active' : 'uzum-tab'">
                    Отвеченные
                    <span x-show="stats.answered > 0"
                          class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full badge-answered"
                          x-text="stats.answered"></span>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-6">

            <!-- Checking Auth -->
            <div x-show="checking" class="max-w-md mx-auto mt-12 flex justify-center">
                <svg class="animate-spin h-8 w-8 text-[#3A007D]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            <!-- Auth Form (shown when not authenticated) -->
            <div x-show="!checking && !authenticated" class="max-w-md mx-auto mt-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Auth Header -->
                    <div class="uzum-gradient px-6 py-5 text-center">
                        <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h2 class="text-white font-bold text-lg">Авторизация Uzum Seller</h2>
                        <p class="text-white/70 text-sm mt-1">Для доступа к отзывам войдите в аккаунт продавца</p>
                    </div>

                    <!-- Tabs -->
                    <div class="flex border-b border-gray-200">
                        <button @click="authTab = 'login'"
                                class="flex-1 py-3 px-4 text-sm transition-colors border-b-2"
                                :class="authTab === 'login' ? 'uzum-tab-active' : 'uzum-tab'">
                            Логин / Пароль
                        </button>
                        <button @click="authTab = 'token'"
                                class="flex-1 py-3 px-4 text-sm transition-colors border-b-2"
                                :class="authTab === 'token' ? 'uzum-tab-active' : 'uzum-tab'">
                            Вставить токен
                        </button>
                    </div>

                    <div class="p-6">
                        <!-- Error message -->
                        <div x-show="authError" x-cloak class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700" x-text="authError"></div>

                        <!-- Login/Password tab -->
                        <div x-show="authTab === 'login'">
                            <form @submit.prevent="doLogin()">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email или логин</label>
                                        <input type="text" x-model="loginForm.login" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#3A007D] focus:border-transparent text-sm"
                                               placeholder="email@example.com">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
                                        <input type="password" x-model="loginForm.password" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#3A007D] focus:border-transparent text-sm"
                                               placeholder="Ваш пароль">
                                    </div>
                                    <button type="submit" :disabled="authLoading" class="w-full px-4 py-3 uzum-btn rounded-xl font-semibold text-sm transition">
                                        <span x-show="!authLoading">Войти в Uzum Seller</span>
                                        <span x-show="authLoading" class="flex items-center justify-center gap-2">
                                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                            Авторизация...
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Token tab -->
                        <div x-show="authTab === 'token'">
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 mb-4">
                                <p class="text-sm font-medium text-blue-800 mb-2">Как получить токен:</p>
                                <ol class="text-xs text-blue-700 space-y-1 list-decimal list-inside">
                                    <li>Откройте <a href="https://seller.uzum.uz" target="_blank" class="underline font-medium">seller.uzum.uz</a> и войдите в аккаунт</li>
                                    <li>Нажмите <kbd class="px-1 py-0.5 bg-blue-100 rounded text-[10px] font-mono">F12</kbd> для открытия DevTools</li>
                                    <li>Перейдите на вкладку <strong>Network</strong> (Сеть)</li>
                                    <li>Обновите страницу и нажмите на любой запрос к <code class="text-[10px] bg-blue-100 px-1 rounded">api-seller.uzum.uz</code></li>
                                    <li>В разделе <strong>Request Headers</strong> найдите <code class="text-[10px] bg-blue-100 px-1 rounded">Authorization</code></li>
                                    <li>Скопируйте значение токена (без слова Bearer)</li>
                                </ol>
                            </div>
                            <form @submit.prevent="doSaveToken()">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                                        <textarea x-model="tokenForm.token" required rows="3"
                                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#3A007D] focus:border-transparent text-sm font-mono"
                                                  placeholder="Вставьте токен сюда..."></textarea>
                                    </div>
                                    <button type="submit" :disabled="authLoading" class="w-full px-4 py-3 uzum-btn rounded-xl font-semibold text-sm transition">
                                        <span x-show="!authLoading">Сохранить токен</span>
                                        <span x-show="authLoading" class="flex items-center justify-center gap-2">
                                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                            Сохранение...
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading Skeleton (when authenticated) -->
            <div x-show="authenticated && loading && reviews.length === 0" class="max-w-4xl mx-auto space-y-4">
                <template x-for="i in 4" :key="i">
                    <div class="bg-white rounded-xl p-5 animate-pulse">
                        <div class="flex items-start space-x-4">
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                                <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                                <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                                <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Error State -->
            <div x-show="authenticated && error && !loading" class="max-w-4xl mx-auto">
                <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                    <svg class="w-12 h-12 text-red-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-red-700 font-medium" x-text="error"></p>
                    <button @click="loadReviews()" class="mt-4 px-4 py-2 uzum-btn rounded-lg text-sm font-medium">
                        Попробовать снова
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div x-show="authenticated && !loading && !error && reviews.length === 0" class="max-w-4xl mx-auto">
                <div class="bg-white rounded-xl p-12 text-center border border-gray-200">
                    <div class="w-16 h-16 uzum-gradient rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Отзывов нет</h3>
                    <p class="text-gray-500 text-sm" x-text="filter === 'unanswered' ? 'Все отзывы уже отвечены — отлично!' : 'Покупатели ещё не оставили отзывы по этому аккаунту.'"></p>
                    <template x-if="filter === 'unanswered'">
                        <button @click="setFilter('all')" class="mt-4 px-4 py-2 uzum-btn-outline rounded-lg text-sm font-medium transition">
                            Показать все отзывы
                        </button>
                    </template>
                </div>
            </div>

            <!-- Reviews List -->
            <div x-show="authenticated && (!loading || reviews.length > 0)" class="max-w-4xl mx-auto space-y-4">
                <template x-for="review in reviews" :key="review.id">
                    <div class="review-card bg-white p-5">
                        <!-- Review Header -->
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start space-x-3 min-w-0">
                                <!-- Avatar -->
                                <div class="w-10 h-10 uzum-gradient rounded-full flex items-center justify-center flex-shrink-0 text-white font-bold text-sm"
                                     x-text="(review.customerName || review.author?.firstName || 'A').charAt(0).toUpperCase()">
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center space-x-2 flex-wrap gap-1">
                                        <span class="font-semibold text-gray-900" x-text="review.customerName || review.author?.firstName || 'Покупатель'"></span>
                                        <!-- Reply status badge -->
                                        <span x-show="review.reply || review.answer" class="px-2 py-0.5 text-xs rounded-full badge-answered font-medium">Отвечен</span>
                                        <span x-show="!review.reply && !review.answer" class="px-2 py-0.5 text-xs rounded-full badge-unanswered font-medium">Без ответа</span>
                                    </div>
                                    <!-- Product Name -->
                                    <p x-show="review.productName || review.productTitle" class="text-xs text-gray-400 mt-0.5 truncate" x-text="review.productName || review.productTitle"></p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3 flex-shrink-0">
                                <!-- Stars -->
                                <div class="flex items-center space-x-0.5">
                                    <template x-for="n in 5" :key="n">
                                        <svg class="w-4 h-4" :class="n <= review.rating ? 'star-filled' : 'star-empty'" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                        </svg>
                                    </template>
                                </div>
                                <!-- Date -->
                                <span class="text-xs text-gray-400" x-text="formatDate(review.createdAt)"></span>
                            </div>
                        </div>

                        <!-- Review Text -->
                        <div x-show="review.text || review.body" class="mt-3">
                            <p class="text-gray-700 text-sm leading-relaxed" x-text="review.text || review.body"></p>
                        </div>

                        <!-- Pros / Cons -->
                        <div x-show="review.pros || review.cons" class="mt-3 flex flex-wrap gap-3">
                            <div x-show="review.pros" class="flex items-start space-x-1.5">
                                <span class="text-green-500 text-xs font-semibold mt-0.5">+</span>
                                <p class="text-xs text-gray-600" x-text="review.pros"></p>
                            </div>
                            <div x-show="review.cons" class="flex items-start space-x-1.5">
                                <span class="text-red-400 text-xs font-semibold mt-0.5">-</span>
                                <p class="text-xs text-gray-600" x-text="review.cons"></p>
                            </div>
                        </div>

                        <!-- Existing Reply -->
                        <div x-show="review.reply || review.answer" class="mt-3 bg-gray-50 rounded-lg p-3 border-l-4 border-[#3A007D]">
                            <p class="text-xs font-semibold text-[#3A007D] mb-1">Ваш ответ:</p>
                            <p class="text-sm text-gray-700" x-text="review.reply || review.answer?.body"></p>
                        </div>

                        <!-- Action Row -->
                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <!-- Order ID chip -->
                                <span x-show="review.orderId"
                                      class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-md font-mono"
                                      x-text="'#' + review.orderId"></span>
                            </div>
                            <button @click="openReplyModal(review)"
                                    class="px-4 py-2 text-sm font-medium rounded-lg transition flex items-center space-x-1.5"
                                    :class="(review.reply || review.answer) ? 'uzum-btn-outline' : 'uzum-btn'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                                <span x-text="(review.reply || review.answer) ? 'Редактировать ответ' : 'Ответить'"></span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Load More -->
                <div x-show="hasMore" class="text-center pt-2">
                    <button @click="loadMore()"
                            :disabled="loading"
                            class="px-6 py-3 bg-white border-2 border-[#3A007D] text-[#3A007D] hover:bg-[#3A007D] hover:text-white rounded-xl font-medium transition disabled:opacity-50 flex items-center space-x-2 mx-auto">
                        <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span>Загрузить ещё</span>
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- Reply Modal -->
    <div x-show="replyModal.show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-4"
         @click.self="replyModal.show = false">

        <div x-show="replyModal.show"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 uzum-gradient rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Ответ на отзыв</h3>
                        <p class="text-xs text-gray-500" x-text="replyModal.review?.customerName || replyModal.review?.author?.firstName || 'Покупатель'"></p>
                    </div>
                </div>
                <button @click="replyModal.show = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Review Preview -->
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-100">
                <div class="flex items-center space-x-1 mb-1">
                    <template x-for="n in 5" :key="n">
                        <svg class="w-3.5 h-3.5" :class="n <= (replyModal.review?.rating || 0) ? 'star-filled' : 'star-empty'" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </template>
                </div>
                <p class="text-sm text-gray-600 line-clamp-3" x-text="replyModal.review?.text || replyModal.review?.body || 'Отзыв без текста'"></p>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-4 space-y-3">
                <!-- AI Generate Button -->
                <button @click="generateAiReply()"
                        :disabled="replyModal.generating"
                        class="w-full px-4 py-2.5 ai-btn rounded-xl font-medium text-sm transition flex items-center justify-center space-x-2">
                    <template x-if="replyModal.generating">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <template x-if="!replyModal.generating">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </template>
                    <span x-text="replyModal.generating ? 'Генерирую...' : 'Сгенерировать AI ответ'"></span>
                </button>

                <!-- Reply Textarea -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Текст ответа</label>
                    <textarea
                        x-model="replyModal.text"
                        rows="5"
                        placeholder="Напишите ответ покупателю..."
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[#3A007D] focus:border-transparent transition"
                    ></textarea>
                    <p class="text-xs text-gray-400 mt-1" x-text="replyModal.text.length + ' символов'"></p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end space-x-3">
                <button @click="replyModal.show = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Отмена
                </button>
                <button @click="submitReply()"
                        :disabled="replyModal.saving || !replyModal.text.trim()"
                        class="px-5 py-2 text-sm font-medium uzum-btn rounded-lg transition flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="replyModal.saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="replyModal.saving ? 'Отправляю...' : 'Отправить ответ'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-show="toast.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-full"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-full"
         class="fixed bottom-6 right-6 z-50 flex items-center space-x-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium"
         :class="toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">
        <svg x-show="toast.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <svg x-show="toast.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <span x-text="toast.message"></span>
    </div>
</div>

{{-- PWA mode --}}
<div class="pwa-only">
    <x-pwa.header title="Отзывы Uzum" :back-url="'/marketplace/' . $accountId" />
    <div class="p-4 text-center text-gray-500 text-sm mt-20">
        Пожалуйста, откройте страницу в браузере для работы с отзывами.
    </div>
</div>

<script>
function uzumReviewsPage() {
    return {
        accountId: @js($accountId),
        // Auth state
        checking: true,
        authenticated: false,
        authTab: 'login',
        authLoading: false,
        authError: null,
        loginForm: { login: '', password: '' },
        tokenForm: { token: '' },
        // Reviews state
        reviews: [],
        loading: false,
        error: null,
        filter: 'unanswered',
        page: 0,
        hasMore: false,
        stats: {
            total: 0,
            unanswered: 0,
            answered: 0,
            avgRating: '0.0',
        },
        replyModal: {
            show: false,
            review: null,
            text: '',
            generating: false,
            saving: false,
        },
        toast: {
            show: false,
            message: '',
            type: 'success',
        },

        async init() {
            await this.checkAuth();
        },

        getHeaders() {
            const headers = window.getAuthHeaders ? window.getAuthHeaders() : { 'Accept': 'application/json' };
            headers['Content-Type'] = 'application/json';
            return headers;
        },

        // ===== Auth Methods =====

        async checkAuth() {
            this.checking = true;
            try {
                const res = await fetch(`/api/uzum-reviews/${this.accountId}/check-auth`, {
                    headers: this.getHeaders()
                });
                const data = await res.json();
                this.authenticated = data.authenticated || false;
                if (this.authenticated) {
                    await this.loadReviews();
                }
            } catch (e) {
                console.error('Check auth error:', e);
            }
            this.checking = false;
        },

        async doLogin() {
            this.authLoading = true;
            this.authError = null;
            try {
                const res = await fetch(`/api/uzum-reviews/${this.accountId}/login`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                    body: JSON.stringify({
                        login: this.loginForm.login,
                        password: this.loginForm.password
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.authenticated = true;
                    this.showToast('Авторизация успешна', 'success');
                    await this.loadReviews();
                } else {
                    let errMsg = data.message || 'Ошибка авторизации';
                    if (data.debug) errMsg += ' (' + data.debug + ')';
                    if (data.status_code) errMsg += ' [HTTP ' + data.status_code + ']';
                    this.authError = errMsg;
                }
            } catch (e) {
                this.authError = 'Ошибка подключения к серверу';
            }
            this.authLoading = false;
        },

        async doSaveToken() {
            this.authLoading = true;
            this.authError = null;
            try {
                const res = await fetch(`/api/uzum-reviews/${this.accountId}/save-token`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                    body: JSON.stringify({ token: this.tokenForm.token })
                });
                const data = await res.json();
                if (data.success) {
                    this.authenticated = true;
                    this.showToast('Токен сохранён', 'success');
                    await this.loadReviews();
                } else {
                    this.authError = data.message || 'Ошибка сохранения токена';
                }
            } catch (e) {
                this.authError = 'Ошибка подключения к серверу';
            }
            this.authLoading = false;
        },

        async disconnect() {
            this.authenticated = false;
            this.reviews = [];
            this.page = 0;
            this.stats = { total: 0, unanswered: 0, answered: 0, avgRating: '0.0' };
        },

        // ===== Reviews Methods =====

        async loadReviews() {
            this.loading = true;
            this.error = null;
            this.page = 0;
            this.reviews = [];

            try {
                const res = await fetch(`/api/uzum-reviews/${this.accountId}/reviews?page=0&size=20`, {
                    headers: this.getHeaders()
                });

                if (res.status === 401) {
                    this.authenticated = false;
                    this.loading = false;
                    return;
                }

                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.message || `Ошибка ${res.status}`);
                }

                const data = await res.json();
                if (data.success) {
                    const allReviews = data.reviews ?? [];
                    this.hasMore = allReviews.length >= 20;
                    this.updateStats({ reviews: allReviews });
                    this.applyFilter(allReviews);
                } else {
                    throw new Error(data.message || 'Ошибка загрузки');
                }
            } catch (e) {
                this.error = e.message || 'Не удалось загрузить отзывы';
            } finally {
                this.loading = false;
            }
        },

        async loadMore() {
            if (this.loading || !this.hasMore) return;
            this.loading = true;
            this.page++;

            try {
                const res = await fetch(`/api/uzum-reviews/${this.accountId}/reviews?page=${this.page}&size=20`, {
                    headers: this.getHeaders()
                });

                if (!res.ok) throw new Error(`Ошибка ${res.status}`);

                const data = await res.json();
                if (data.success) {
                    const newReviews = data.reviews ?? [];
                    this.reviews = [...this.reviews, ...newReviews];
                    this.hasMore = newReviews.length >= 20;
                }
            } catch (e) {
                this.showToast('Не удалось загрузить отзывы: ' + e.message, 'error');
                this.page--;
            } finally {
                this.loading = false;
            }
        },

        applyFilter(allReviews) {
            if (!allReviews) allReviews = this._allReviews || [];
            this._allReviews = allReviews;

            if (this.filter === 'unanswered') {
                this.reviews = allReviews.filter(r => !r.reply && !r.answer);
            } else if (this.filter === 'answered') {
                this.reviews = allReviews.filter(r => r.reply || r.answer);
            } else {
                this.reviews = [...allReviews];
            }
        },

        async setFilter(value) {
            if (this.filter === value) return;
            this.filter = value;
            this.applyFilter();
        },

        updateStats(data) {
            const all = data.reviews ?? [];
            const answered = all.filter(r => r.reply || r.answer).length;
            const sum = all.reduce((a, r) => a + (r.rating || 0), 0);
            this.stats = {
                total: all.length,
                unanswered: all.length - answered,
                answered: answered,
                avgRating: all.length ? (sum / all.length).toFixed(1) : '0.0',
            };
        },

        openReplyModal(review) {
            this.replyModal = {
                show: true,
                review: review,
                text: review.reply || review.answer?.body || '',
                generating: false,
                saving: false,
            };
        },

        async generateAiReply() {
            if (this.replyModal.generating) return;
            this.replyModal.generating = true;

            try {
                const res = await fetch(`/api/uzum-reviews/${this.accountId}/ai-reply`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                    body: JSON.stringify({
                        review_id: this.replyModal.review.id,
                        review_text: this.replyModal.review.text || this.replyModal.review.body || '',
                        rating: this.replyModal.review.rating,
                        product_name: this.replyModal.review.productName || this.replyModal.review.productTitle || '',
                    }),
                });

                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.message || `Ошибка ${res.status}`);
                }

                const data = await res.json();
                this.replyModal.text = data.reply ?? data.content ?? data.text ?? '';
            } catch (e) {
                this.showToast('Ошибка генерации: ' + e.message, 'error');
            } finally {
                this.replyModal.generating = false;
            }
        },

        async submitReply() {
            if (this.replyModal.saving || !this.replyModal.text.trim()) return;
            this.replyModal.saving = true;

            try {
                const res = await fetch(`/api/uzum-reviews/${this.accountId}/reply`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                    body: JSON.stringify({
                        review_id: this.replyModal.review.id,
                        content: this.replyModal.text.trim()
                    }),
                });

                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.message || `Ошибка ${res.status}`);
                }

                // Обновить отзыв в списке
                const idx = this.reviews.findIndex(r => r.id === this.replyModal.review.id);
                if (idx !== -1) {
                    this.reviews[idx] = { ...this.reviews[idx], reply: this.replyModal.text.trim(), answer: { body: this.replyModal.text.trim() } };
                    // Убрать из списка неотвеченных
                    if (this.filter === 'unanswered') {
                        this.reviews.splice(idx, 1);
                    }
                }

                // Also update _allReviews
                if (this._allReviews) {
                    const allIdx = this._allReviews.findIndex(r => r.id === this.replyModal.review.id);
                    if (allIdx !== -1) {
                        this._allReviews[allIdx] = { ...this._allReviews[allIdx], reply: this.replyModal.text.trim(), answer: { body: this.replyModal.text.trim() } };
                    }
                    this.updateStats({ reviews: this._allReviews });
                }

                this.replyModal.show = false;
                this.showToast('Ответ успешно отправлен', 'success');
            } catch (e) {
                this.showToast('Не удалось отправить: ' + e.message, 'error');
            } finally {
                this.replyModal.saving = false;
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            try {
                const d = new Date(dateStr);
                return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' });
            } catch {
                return dateStr;
            }
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3500);
        },
    };
}
</script>
@endsection
