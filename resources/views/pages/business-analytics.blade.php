@extends('layouts.app')

@section('content')

{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="businessAnalyticsPage()" x-init="init()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar></x-sidebar>
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Бизнес-аналитика</h1>
                    <p class="text-sm text-gray-500">ABC, ABCXYZ и SWOT анализы</p>
                </div>
                <div class="flex items-center space-x-3">
                    <template x-if="activeTab !== 'swot'">
                        <select x-model="period" @change="loadCurrentTab()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="today">Сегодня</option>
                            <option value="7days">7 дней</option>
                            <option value="30days">30 дней</option>
                            <option value="90days">90 дней</option>
                            <option value="365days">Год</option>
                        </select>
                    </template>
                    <button @click="loadCurrentTab()" :disabled="loading" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!loading">Обновить</span>
                        <span x-show="loading">Загрузка...</span>
                    </button>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex space-x-1 mt-4 bg-gray-100 rounded-lg p-1">
                <button @click="switchTab('abc')"
                        :class="activeTab === 'abc' ? 'bg-white shadow text-blue-700' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition">
                    ABC Анализ
                </button>
                <button @click="switchTab('abcxyz')"
                        :class="activeTab === 'abcxyz' ? 'bg-white shadow text-blue-700' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition">
                    ABCXYZ Клиенты
                </button>
                <button @click="switchTab('swot')"
                        :class="activeTab === 'swot' ? 'bg-white shadow text-blue-700' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition">
                    SWOT Анализ
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            {{-- ABC Анализ --}}
            <div x-show="activeTab === 'abc'" x-cloak>
                @include('pages.business-analytics.abc-tab')
            </div>

            {{-- ABCXYZ Анализ --}}
            <div x-show="activeTab === 'abcxyz'" x-cloak>
                @include('pages.business-analytics.abcxyz-tab')
            </div>

            {{-- SWOT Анализ --}}
            <div x-show="activeTab === 'swot'" x-cloak>
                @include('pages.business-analytics.swot-tab')
            </div>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="businessAnalyticsPage()" x-init="init()" style="background: #f2f2f7;">
    <x-pwa-header title="Бизнес-аналитика">
        <template x-if="activeTab !== 'swot'">
            <button @click="showPeriodSheet = true" class="native-header-btn">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </button>
        </template>
    </x-pwa-header>

    {{-- PWA Tabs --}}
    <div class="sticky top-12 z-10 bg-white/80 backdrop-blur-md border-b border-gray-200/50 px-4 py-2">
        <div class="flex space-x-1 bg-gray-100 rounded-lg p-1">
            <button @click="switchTab('abc')"
                    :class="activeTab === 'abc' ? 'bg-white shadow text-blue-700' : 'text-gray-500'"
                    class="flex-1 py-1.5 px-3 rounded-md text-xs font-medium transition">ABC</button>
            <button @click="switchTab('abcxyz')"
                    :class="activeTab === 'abcxyz' ? 'bg-white shadow text-blue-700' : 'text-gray-500'"
                    class="flex-1 py-1.5 px-3 rounded-md text-xs font-medium transition">ABCXYZ</button>
            <button @click="switchTab('swot')"
                    :class="activeTab === 'swot' ? 'bg-white shadow text-blue-700' : 'text-gray-500'"
                    class="flex-1 py-1.5 px-3 rounded-md text-xs font-medium transition">SWOT</button>
        </div>
    </div>

    <div class="px-4 pt-3 pb-24">
        <div x-show="activeTab === 'abc'" x-cloak>
            @include('pages.business-analytics.abc-tab')
        </div>
        <div x-show="activeTab === 'abcxyz'" x-cloak>
            @include('pages.business-analytics.abcxyz-tab')
        </div>
        <div x-show="activeTab === 'swot'" x-cloak>
            @include('pages.business-analytics.swot-tab')
        </div>
    </div>

    {{-- Period Bottom Sheet --}}
    <template x-if="showPeriodSheet">
        <div class="fixed inset-0 z-50" @click.self="showPeriodSheet = false">
            <div class="absolute inset-0 bg-black/30" @click="showPeriodSheet = false"></div>
            <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl p-6 safe-area-bottom">
                <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <h3 class="text-lg font-semibold mb-4">Выбрать период</h3>
                <div class="space-y-2">
                    <template x-for="p in [{v:'today',l:'Сегодня'},{v:'7days',l:'7 дней'},{v:'30days',l:'30 дней'},{v:'90days',l:'90 дней'},{v:'365days',l:'Год'}]">
                        <button @click="period = p.v; showPeriodSheet = false; loadCurrentTab()"
                                :class="period === p.v ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-50 text-gray-700'"
                                class="w-full py-3 px-4 rounded-xl text-left font-medium border transition">
                            <span x-text="p.l"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>

@endsection

@push('scripts')
<script>
function businessAnalyticsPage() {
    return {
        loading: false,
        activeTab: 'abc',
        period: '30days',
        showPeriodSheet: false,

        // ABC данные
        abcData: {
            summary: {
                total_products: 0,
                total_revenue: 0,
                categories: {
                    A: { count: 0, revenue: 0, percentage: 0, assortment_percentage: 20 },
                    B: { count: 0, revenue: 0, percentage: 0, assortment_percentage: 30 },
                    C: { count: 0, revenue: 0, percentage: 0, assortment_percentage: 50 }
                }
            },
            products: []
        },
        abcLoaded: false,

        // ABCXYZ данные
        abcxyzData: {
            matrix: {},
            summary: { total_customers: 0, total_revenue: 0, period_weeks: 0 },
            thresholds: { A: 10000, B: 5000, C: 0 }
        },
        abcxyzLoaded: false,

        // SWOT данные
        swot: {
            strengths: [],
            weaknesses: [],
            opportunities: [],
            threats: []
        },
        newItem: { strengths: '', weaknesses: '', opportunities: '', threats: '' },
        swotSaving: false,
        swotLoaded: false,

        async init() {
            await this.loadAbcData();
        },

        switchTab(tab) {
            this.activeTab = tab;
            if (tab === 'abc' && !this.abcLoaded) this.loadAbcData();
            if (tab === 'abcxyz' && !this.abcxyzLoaded) this.loadAbcxyzData();
            if (tab === 'swot' && !this.swotLoaded) this.loadSwotData();
        },

        loadCurrentTab() {
            if (this.activeTab === 'abc') this.loadAbcData();
            else if (this.activeTab === 'abcxyz') this.loadAbcxyzData();
            else if (this.activeTab === 'swot') this.loadSwotData();
        },

        async loadAbcData() {
            this.loading = true;
            try {
                const response = await window.api.get('/business-analytics/abc', {
                    params: {
                        period: this.period,
                        company_id: this.$store.auth?.currentCompany?.id
                    },
                    silent: true
                });
                if (response?.data) {
                    this.abcData = response.data;
                    this.abcLoaded = true;
                }
            } catch (e) { console.error('ABC load error:', e); }
            this.loading = false;
        },

        async loadAbcxyzData() {
            this.loading = true;
            try {
                const response = await window.api.get('/business-analytics/abcxyz', {
                    params: {
                        period: this.period,
                        company_id: this.$store.auth?.currentCompany?.id
                    },
                    silent: true
                });
                if (response?.data) {
                    this.abcxyzData = response.data;
                    this.abcxyzLoaded = true;
                }
            } catch (e) { console.error('ABCXYZ load error:', e); }
            this.loading = false;
        },

        async loadSwotData() {
            this.loading = true;
            try {
                const response = await window.api.get('/business-analytics/swot', {
                    params: {
                        company_id: this.$store.auth?.currentCompany?.id
                    },
                    silent: true
                });
                if (response?.data) {
                    this.swot = {
                        strengths: response.data.strengths || [],
                        weaknesses: response.data.weaknesses || [],
                        opportunities: response.data.opportunities || [],
                        threats: response.data.threats || []
                    };
                    this.swotLoaded = true;
                }
            } catch (e) { console.error('SWOT load error:', e); }
            this.loading = false;
        },

        async saveSwot() {
            this.swotSaving = true;
            try {
                await window.api.post('/business-analytics/swot', {
                    ...this.swot,
                    company_id: this.$store.auth?.currentCompany?.id
                });
                if (window.$toast) window.$toast.success('SWOT-анализ сохранён');
            } catch (e) {
                console.error('SWOT save error:', e);
                if (window.$toast) window.$toast.error('Ошибка сохранения');
            }
            this.swotSaving = false;
        },

        addSwotItem(type) {
            if (this.newItem[type] && this.newItem[type].trim()) {
                this.swot[type].push(this.newItem[type].trim());
                this.newItem[type] = '';
            }
        },

        removeSwotItem(type, index) {
            this.swot[type].splice(index, 1);
        },

        formatMoney(value) {
            if (!value) return '0';
            return new Intl.NumberFormat('ru-RU').format(Math.round(value));
        },

        getCategoryColor(cat) {
            return { A: 'green', B: 'yellow', C: 'red' }[cat] || 'gray';
        },

        getSegmentColor(segment) {
            const colors = {
                AX: 'bg-green-600 text-white',
                AY: 'bg-green-500 text-white',
                AZ: 'bg-green-400 text-white',
                BX: 'bg-yellow-500 text-white',
                BY: 'bg-yellow-400 text-gray-900',
                BZ: 'bg-yellow-300 text-gray-900',
                CX: 'bg-red-400 text-white',
                CY: 'bg-red-300 text-gray-900',
                CZ: 'bg-red-200 text-gray-900',
            };
            return colors[segment] || 'bg-gray-100 text-gray-700';
        },

        getSegmentLabel(segment) {
            const labels = {
                AX: 'VIP ежедневные',
                AY: 'VIP еженедельные',
                AZ: 'VIP редкие',
                BX: 'Средние ежедневные',
                BY: 'Средние еженедельные',
                BZ: 'Средние редкие',
                CX: 'Малые ежедневные',
                CY: 'Малые еженедельные',
                CZ: 'Малые редкие',
            };
            return labels[segment] || segment;
        }
    }
}
</script>
@endpush
