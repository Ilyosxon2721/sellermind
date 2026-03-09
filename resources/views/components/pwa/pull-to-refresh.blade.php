{{--
    PWA Pull to Refresh Component
    Native-style pull to refresh functionality

    @props
    - callback: string - Alpine.js method to call on refresh
--}}

@props([
    'callback' => 'refresh',
])

<div
    x-data="{
        pulling: false,
        refreshing: false,
        pullDistance: 0,
        threshold: 80,
        maxPull: 120,
        startY: 0,

        init() {
            // Only enable if at top of page
            this.$el.addEventListener('touchstart', (e) => this.handleTouchStart(e), { passive: true });
            this.$el.addEventListener('touchmove', (e) => this.handleTouchMove(e), { passive: false });
            this.$el.addEventListener('touchend', (e) => this.handleTouchEnd(e), { passive: true });
        },

        handleTouchStart(e) {
            if (window.scrollY === 0 && !this.refreshing) {
                this.startY = e.touches[0].clientY;
                this.pulling = true;
            }
        },

        handleTouchMove(e) {
            if (!this.pulling || this.refreshing) return;
            if (window.scrollY > 0) {
                this.reset();
                return;
            }

            const currentY = e.touches[0].clientY;
            const distance = currentY - this.startY;

            if (distance > 0) {
                e.preventDefault();
                // Apply resistance
                this.pullDistance = Math.min(distance * 0.5, this.maxPull);
            }
        },

        handleTouchEnd() {
            if (!this.pulling) return;

            if (this.pullDistance >= this.threshold && !this.refreshing) {
                this.triggerRefresh();
            } else {
                this.reset();
            }
        },

        async triggerRefresh() {
            this.refreshing = true;
            this.pullDistance = 60;

            // Haptic feedback
            if (window.SmHaptic) {
                window.SmHaptic.medium();
            } else if (navigator.vibrate) {
                navigator.vibrate(20);
            }

            try {
                // Call the refresh callback
                if (typeof this.$parent.{{ $callback }} === 'function') {
                    await this.$parent.{{ $callback }}();
                } else if (typeof this.{{ $callback }} === 'function') {
                    await this.{{ $callback }}();
                } else {
                    // Dispatch custom event as fallback
                    this.$dispatch('pull-refresh');
                }
            } catch (error) {
                console.error('Refresh failed:', error);
            } finally {
                // Animate back
                setTimeout(() => {
                    this.reset();
                }, 300);
            }
        },

        reset() {
            this.pulling = false;
            this.refreshing = false;
            this.pullDistance = 0;
        }
    }"
    class="relative"
>
    {{-- Pull Indicator --}}
    <div
        class="absolute left-1/2 -translate-x-1/2 flex items-center justify-center transition-transform duration-200 z-10"
        :style="`top: ${pullDistance - 40}px; opacity: ${Math.min(pullDistance / threshold, 1)};`"
        x-show="pullDistance > 0 || refreshing"
    >
        <div
            class="w-8 h-8 flex items-center justify-center bg-white dark:bg-gray-700 rounded-full shadow-lg"
        >
            {{-- Spinner --}}
            <svg
                x-show="refreshing"
                class="w-5 h-5 text-blue-600 animate-spin"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>

            {{-- Arrow --}}
            <svg
                x-show="!refreshing"
                class="w-5 h-5 text-gray-600 dark:text-gray-300 transition-transform duration-200"
                :class="pullDistance >= threshold ? 'rotate-180' : ''"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    </div>

    {{-- Content --}}
    <div
        class="transition-transform duration-200"
        :style="`transform: translateY(${pullDistance}px);`"
    >
        {{ $slot }}
    </div>
</div>
