{{--
    PWA Period Selector Component
    Native-style segment control for period selection with custom date range picker

    @props
    - periods: array - Available periods (default: today, week, month, year, custom)
    - selected: string - Currently selected period
    - showComparison: bool - Show comparison toggle
    - comparisonEnabled: bool - Whether comparison is enabled
--}}

@props([
    'periods' => [
        'today' => 'Сегодня',
        'week' => 'Неделя',
        'month' => 'Месяц',
        'year' => 'Год',
        'custom' => 'Период',
    ],
    'selected' => 'month',
    'showComparison' => true,
    'comparisonEnabled' => false,
])

<div
    x-data="{
        selected: @js($selected),
        periods: @js($periods),
        showDatePicker: false,
        customStartDate: '',
        customEndDate: '',
        comparisonEnabled: @js($comparisonEnabled),

        init() {
            // Set default custom dates to last 30 days
            const today = new Date();
            const thirtyDaysAgo = new Date(today);
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

            this.customEndDate = this.formatDate(today);
            this.customStartDate = this.formatDate(thirtyDaysAgo);
        },

        formatDate(date) {
            return date.toISOString().split('T')[0];
        },

        selectPeriod(period) {
            // Haptic feedback
            if (window.SmHaptic) {
                window.SmHaptic.light();
            } else if (navigator.vibrate) {
                navigator.vibrate(10);
            }

            if (period === 'custom') {
                this.showDatePicker = true;
            } else {
                this.selected = period;
                this.$dispatch('period-changed', {
                    period: period,
                    comparison: this.comparisonEnabled
                });
            }
        },

        applyCustomPeriod() {
            if (this.customStartDate && this.customEndDate) {
                this.selected = 'custom';
                this.showDatePicker = false;
                this.$dispatch('period-changed', {
                    period: 'custom',
                    startDate: this.customStartDate,
                    endDate: this.customEndDate,
                    comparison: this.comparisonEnabled
                });
            }
        },

        toggleComparison() {
            this.comparisonEnabled = !this.comparisonEnabled;

            // Haptic feedback
            if (window.SmHaptic) {
                window.SmHaptic.light();
            }

            this.$dispatch('comparison-toggled', {
                enabled: this.comparisonEnabled,
                period: this.selected
            });
        },

        getCustomLabel() {
            if (this.selected === 'custom' && this.customStartDate && this.customEndDate) {
                const start = new Date(this.customStartDate);
                const end = new Date(this.customEndDate);
                const formatShort = (d) => {
                    const day = d.getDate();
                    const months = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
                    return `${day} ${months[d.getMonth()]}`;
                };
                return `${formatShort(start)} - ${formatShort(end)}`;
            }
            return 'Период';
        }
    }"
    {{ $attributes->merge(['class' => 'pwa-only sm-period-selector']) }}
>
    {{-- Segment Control --}}
    <div class="sm-segment-control">
        <template x-for="(label, key) in periods" :key="key">
            <button
                type="button"
                class="sm-segment-item"
                :class="{ 'active': selected === key }"
                @click="selectPeriod(key)"
            >
                <span x-text="key === 'custom' && selected === 'custom' ? getCustomLabel() : label"></span>
            </button>
        </template>
        {{-- Active indicator --}}
        <div
            class="sm-segment-indicator"
            :style="`transform: translateX(${Object.keys(periods).indexOf(selected) * 100}%); width: ${100 / Object.keys(periods).length}%`"
        ></div>
    </div>

    {{-- Comparison Toggle --}}
    @if($showComparison)
    <div class="sm-comparison-toggle">
        <label class="flex items-center justify-between cursor-pointer" @click="toggleComparison()">
            <span class="native-caption">Сравнить с прошлым периодом</span>
            <div
                class="sm-switch"
                :class="{ 'active': comparisonEnabled }"
            >
                <div class="sm-switch-thumb"></div>
            </div>
        </label>
    </div>
    @endif

    {{-- Custom Date Picker Sheet --}}
    <div
        x-show="showDatePicker"
        x-cloak
        @click.self="showDatePicker = false"
        class="native-modal-overlay"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="native-sheet" @click.stop>
            <div class="native-sheet-handle"></div>

            <h3 class="native-headline mb-4">Выберите период</h3>

            <div class="space-y-4">
                {{-- Start Date --}}
                <div>
                    <label class="native-caption block mb-2">Начало</label>
                    <input
                        type="date"
                        x-model="customStartDate"
                        class="native-input w-full"
                    >
                </div>

                {{-- End Date --}}
                <div>
                    <label class="native-caption block mb-2">Конец</label>
                    <input
                        type="date"
                        x-model="customEndDate"
                        class="native-input w-full"
                    >
                </div>

                {{-- Quick Presets --}}
                <div class="pt-2">
                    <p class="native-caption mb-2">Быстрый выбор</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="px-3 py-1.5 text-sm bg-gray-100 rounded-lg text-gray-700"
                            @click="
                                const end = new Date();
                                const start = new Date();
                                start.setDate(start.getDate() - 7);
                                customStartDate = formatDate(start);
                                customEndDate = formatDate(end);
                            "
                        >7 дней</button>
                        <button
                            type="button"
                            class="px-3 py-1.5 text-sm bg-gray-100 rounded-lg text-gray-700"
                            @click="
                                const end = new Date();
                                const start = new Date();
                                start.setDate(start.getDate() - 14);
                                customStartDate = formatDate(start);
                                customEndDate = formatDate(end);
                            "
                        >14 дней</button>
                        <button
                            type="button"
                            class="px-3 py-1.5 text-sm bg-gray-100 rounded-lg text-gray-700"
                            @click="
                                const end = new Date();
                                const start = new Date();
                                start.setDate(start.getDate() - 30);
                                customStartDate = formatDate(start);
                                customEndDate = formatDate(end);
                            "
                        >30 дней</button>
                        <button
                            type="button"
                            class="px-3 py-1.5 text-sm bg-gray-100 rounded-lg text-gray-700"
                            @click="
                                const end = new Date();
                                const start = new Date();
                                start.setDate(start.getDate() - 90);
                                customStartDate = formatDate(start);
                                customEndDate = formatDate(end);
                            "
                        >90 дней</button>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 mt-6">
                <button
                    type="button"
                    class="native-btn native-btn-secondary flex-1"
                    @click="showDatePicker = false"
                >Отмена</button>
                <button
                    type="button"
                    class="native-btn native-btn-primary flex-1"
                    @click="applyCustomPeriod()"
                >Применить</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Segment Control */
.pwa-mode .sm-segment-control {
    display: flex;
    position: relative;
    background: rgba(118, 118, 128, 0.12);
    border-radius: 9px;
    padding: 2px;
}

.pwa-mode .sm-segment-item {
    flex: 1;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 500;
    color: #000;
    background: none;
    border: none;
    cursor: pointer;
    z-index: 1;
    transition: color 0.2s;
    -webkit-tap-highlight-color: transparent;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pwa-mode .sm-segment-item.active {
    color: #000;
}

.pwa-mode .sm-segment-indicator {
    position: absolute;
    top: 2px;
    left: 2px;
    bottom: 2px;
    background: #fff;
    border-radius: 7px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;
}

/* Comparison Toggle */
.pwa-mode .sm-comparison-toggle {
    margin-top: 12px;
    padding: 12px 16px;
    background: white;
    border-radius: 10px;
}

/* Switch */
.pwa-mode .sm-switch {
    position: relative;
    width: 51px;
    height: 31px;
    background: #e9e9ea;
    border-radius: 16px;
    transition: background 0.2s;
    flex-shrink: 0;
}

.pwa-mode .sm-switch.active {
    background: #34C759;
}

.pwa-mode .sm-switch-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 27px;
    height: 27px;
    background: white;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s;
}

.pwa-mode .sm-switch.active .sm-switch-thumb {
    transform: translateX(20px);
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .pwa-mode .sm-segment-control {
        background: rgba(118, 118, 128, 0.24);
    }

    .pwa-mode .sm-segment-item {
        color: #fff;
    }

    .pwa-mode .sm-segment-indicator {
        background: #636366;
    }

    .pwa-mode .sm-comparison-toggle {
        background: #1c1c1e;
    }

    .pwa-mode .sm-switch {
        background: #39393d;
    }
}
</style>
