{{--
    PWA Promotions Page
    Native-style promotions list with quick actions and filters
--}}

<x-layouts.pwa :title="'Акции'" :page-title="'Акции'">

    <x-slot name="topBar">
        <header
            class="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
            style="padding-top: var(--safe-area-inset-top);"
        >
            <div class="flex items-center justify-between px-4 h-12">
                {{-- Left: Back --}}
                <div class="flex items-center min-w-[48px]">
                    <a
                        href="/dashboard"
                        class="p-2 -ml-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform"
                        onclick="if(window.SmHaptic) window.SmHaptic.light()"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                </div>

                {{-- Center: Title --}}
                <div class="flex-1 text-center">
                    <h1 class="text-base font-semibold text-gray-900 dark:text-white">Акции и Скидки</h1>
                </div>

                {{-- Right: Add Button --}}
                <div class="flex items-center min-w-[48px] justify-end">
                    <button
                        @click="showCreateSheet = true; triggerHaptic()"
                        type="button"
                        class="p-2 rounded-lg text-blue-600 dark:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>
    </x-slot>

    <x-slot name="skeleton">
        <div class="px-4 pt-3 space-y-4">
            {{-- Filter Skeleton --}}
            <div class="flex space-x-2">
                @for($i = 0; $i < 3; $i++)
                    <div class="skeleton h-8 w-24 rounded-full"></div>
                @endfor
            </div>

            {{-- Cards Skeleton --}}
            @for($i = 0; $i < 4; $i++)
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm">
                    <div class="flex items-start justify-between mb-3">
                        <div class="skeleton h-5 w-32"></div>
                        <div class="skeleton h-6 w-12 rounded-full"></div>
                    </div>
                    <div class="skeleton h-3 w-full mb-2"></div>
                    <div class="skeleton h-3 w-2/3"></div>
                </div>
            @endfor
        </div>
    </x-slot>

    {{-- Main Content --}}
    <div
        x-data="promotionsPwa()"
        x-init="init()"
        class="min-h-full"
    >
        <x-pwa.pull-to-refresh callback="loadPromotions">
            <div class="px-4 pt-3 pb-6 space-y-4">

                {{-- Filter Tabs --}}
                <div class="flex space-x-2 overflow-x-auto scrollbar-hide pb-1">
                    <template x-for="f in filters" :key="f.value">
                        <button
                            @click="setFilter(f.value)"
                            type="button"
                            class="flex-shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-colors active:scale-95"
                            :class="filter === f.value
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 shadow-sm'"
                            x-text="f.label"
                            onclick="if(navigator.vibrate) navigator.vibrate(10)"
                        ></button>
                    </template>
                </div>

                {{-- Quick Action: Find Slow Products --}}
                <button
                    @click="detectSlowMoving(); triggerHaptic()"
                    class="w-full bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-2xl p-4 shadow-sm active:scale-[0.98] transition-transform"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-semibold">Найти медленные товары</p>
                                <p class="text-sm opacity-80">Автоматический подбор для акций</p>
                            </div>
                        </div>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                        </svg>
                    </div>
                </button>

                {{-- Empty State --}}
                <template x-if="!loading && filteredPromotions.length === 0">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm text-center">
                        <div class="w-16 h-16 mx-auto mb-4 flex items-center justify-center bg-purple-100 dark:bg-purple-900/30 rounded-full">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Нет акций</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Создайте первую акцию для увеличения продаж</p>
                        <button
                            @click="showCreateSheet = true; triggerHaptic()"
                            class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-medium active:scale-95 transition-transform"
                        >
                            Создать акцию
                        </button>
                    </div>
                </template>

                {{-- Promotions List --}}
                <div class="space-y-3" x-show="filteredPromotions.length > 0">
                    <template x-for="promotion in filteredPromotions" :key="promotion.id">
                        <div
                            class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm active:scale-[0.98] transition-transform"
                            @click="openPromotion(promotion)"
                        >
                            {{-- Header --}}
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-semibold text-gray-900 dark:text-white truncate" x-text="promotion.name"></h3>
                                        <span
                                            x-show="promotion.is_automatic"
                                            class="px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-xs rounded font-medium"
                                        >
                                            Авто
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2" x-text="promotion.description"></p>
                                </div>
                                <span
                                    class="ml-2 flex-shrink-0 px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="getStatusClass(promotion.status)"
                                    x-text="getStatusLabel(promotion.status)"
                                ></span>
                            </div>

                            {{-- Stats --}}
                            <div class="grid grid-cols-3 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Скидка</p>
                                    <p class="text-sm font-semibold text-purple-600 dark:text-purple-400"
                                       x-text="promotion.discount_value + (promotion.type === 'percentage' ? '%' : ' P')"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Товаров</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="promotion.products_count || 0"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Продаж</p>
                                    <p class="text-sm font-semibold text-green-600 dark:text-green-400" x-text="promotion.sales_count || 0"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Loading --}}
                <div x-show="loading" class="space-y-3">
                    @for($i = 0; $i < 4; $i++)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm animate-pulse">
                            <div class="flex items-start justify-between mb-3">
                                <div class="skeleton h-5 w-32"></div>
                                <div class="skeleton h-6 w-16 rounded-full"></div>
                            </div>
                            <div class="skeleton h-3 w-full mb-2"></div>
                            <div class="skeleton h-3 w-2/3"></div>
                        </div>
                    @endfor
                </div>

            </div>
        </x-pwa.pull-to-refresh>

        {{-- Create Promotion Sheet --}}
        <x-pwa.bottom-sheet x-model="showCreateSheet" title="Создать акцию">
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Название</label>
                    <input
                        type="text"
                        x-model="newPromotion.name"
                        placeholder="Летняя распродажа"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Скидка (%)</label>
                    <input
                        type="number"
                        x-model="newPromotion.discount_value"
                        placeholder="10"
                        min="1"
                        max="99"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Описание</label>
                    <textarea
                        x-model="newPromotion.description"
                        rows="2"
                        placeholder="Описание акции..."
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                    ></textarea>
                </div>
                <button
                    @click="createPromotion()"
                    :disabled="!newPromotion.name || !newPromotion.discount_value"
                    class="w-full py-3 bg-blue-600 text-white rounded-xl font-semibold active:scale-[0.98] transition-transform disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Создать
                </button>
            </div>
        </x-pwa.bottom-sheet>
    </div>

    @push('scripts')
    <script>
    function promotionsPwa() {
        return {
            loading: true,
            promotions: [],
            filter: 'all',
            filters: [
                { value: 'all', label: 'Все' },
                { value: 'active', label: 'Активные' },
                { value: 'scheduled', label: 'Запланированы' },
                { value: 'ended', label: 'Завершены' },
            ],
            showCreateSheet: false,
            newPromotion: {
                name: '',
                discount_value: '',
                description: '',
                type: 'percentage',
            },

            async init() {
                await this.loadPromotions();
            },

            get filteredPromotions() {
                if (this.filter === 'all') return this.promotions;
                return this.promotions.filter(p => p.status === this.filter);
            },

            async loadPromotions() {
                this.loading = true;

                try {
                    const companyId = this.$store?.auth?.currentCompany?.id;
                    if (!companyId) {
                        this.loading = false;
                        return;
                    }

                    const token = this.$store?.auth?.token || localStorage.getItem('_x_auth_token') || '';
                    const headers = {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                    if (token && token !== 'session-auth') {
                        headers['Authorization'] = 'Bearer ' + token;
                    }

                    const response = await fetch('/api/v1/promotions?company_id=' + companyId, { headers });

                    if (!response.ok) throw new Error('Failed to load promotions');

                    const data = await response.json();
                    this.promotions = data.data || data.promotions || data || [];

                } catch (error) {
                    console.error('Error loading promotions:', error);
                } finally {
                    this.loading = false;
                }
            },

            async setFilter(newFilter) {
                this.filter = newFilter;
            },

            async detectSlowMoving() {
                // Redirect to slow moving detection
                window.location.href = '/promotions?action=detect-slow';
            },

            openPromotion(promotion) {
                window.location.href = '/promotions/' + promotion.id;
            },

            async createPromotion() {
                try {
                    const companyId = this.$store?.auth?.currentCompany?.id;
                    if (!companyId) return;

                    const token = this.$store?.auth?.token || localStorage.getItem('_x_auth_token') || '';
                    const headers = {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    };
                    if (token && token !== 'session-auth') {
                        headers['Authorization'] = 'Bearer ' + token;
                    }

                    const response = await fetch('/api/v1/promotions', {
                        method: 'POST',
                        headers,
                        body: JSON.stringify({
                            company_id: companyId,
                            ...this.newPromotion,
                        }),
                    });

                    if (!response.ok) throw new Error('Failed to create promotion');

                    this.showCreateSheet = false;
                    this.newPromotion = { name: '', discount_value: '', description: '', type: 'percentage' };
                    await this.loadPromotions();

                } catch (error) {
                    console.error('Error creating promotion:', error);
                }
            },

            triggerHaptic() {
                if (navigator.vibrate) navigator.vibrate(10);
            },

            getStatusClass(status) {
                const classes = {
                    'active': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                    'scheduled': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                    'ended': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                    'draft': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                };
                return classes[status] || 'bg-gray-100 text-gray-700';
            },

            getStatusLabel(status) {
                const labels = {
                    'active': 'Активна',
                    'scheduled': 'Запланирована',
                    'ended': 'Завершена',
                    'draft': 'Черновик',
                };
                return labels[status] || status || 'Активна';
            },
        };
    }
    </script>
    @endpush

</x-layouts.pwa>
