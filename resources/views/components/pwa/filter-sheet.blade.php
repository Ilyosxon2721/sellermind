{{--
    PWA Filter Sheet Component
    Bottom sheet with advanced filters

    @props
    - id: string - Unique ID for the sheet
    - title: string - Sheet title
--}}

@props([
    'id' => 'filterSheet',
    'title' => 'Фильтры',
])

<div
    x-data="{
        open: false,
        startY: 0,
        currentY: 0,
        isDragging: false,

        show() {
            this.open = true;
            document.body.style.overflow = 'hidden';
        },

        hide() {
            this.open = false;
            document.body.style.overflow = '';
        },

        handleTouchStart(e) {
            this.startY = e.touches[0].clientY;
            this.isDragging = true;
        },

        handleTouchMove(e) {
            if (!this.isDragging) return;
            this.currentY = e.touches[0].clientY;
            const delta = this.currentY - this.startY;
            if (delta > 0) {
                this.$refs.content.style.transform = `translateY(${delta}px)`;
            }
        },

        handleTouchEnd() {
            if (!this.isDragging) return;
            this.isDragging = false;
            const delta = this.currentY - this.startY;
            this.$refs.content.style.transform = '';

            if (delta > 100) {
                this.hide();
            }
        },

        triggerHaptic() {
            if (window.SmHaptic) {
                window.SmHaptic.light();
            } else if (navigator.vibrate) {
                navigator.vibrate(10);
            }
        }
    }"
    x-show="open"
    x-cloak
    @open-{{ $id }}.window="show()"
    @close-{{ $id }}.window="hide()"
    @keydown.escape.window="hide()"
    class="fixed inset-0 z-50"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="hide()"
        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
    ></div>

    {{-- Sheet Content --}}
    <div
        x-ref="content"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
        class="absolute bottom-0 inset-x-0 bg-white dark:bg-gray-800 rounded-t-2xl shadow-xl max-h-[85vh] flex flex-col"
        style="padding-bottom: env(safe-area-inset-bottom, 0px);"
    >
        {{-- Handle --}}
        <div class="flex justify-center pt-3 pb-2">
            <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
        </div>

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 pb-3 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h2>
            <div class="flex items-center space-x-3">
                {{-- Reset Button --}}
                @if(isset($reset))
                    {{ $reset }}
                @endif

                {{-- Close Button --}}
                <button
                    @click="hide(); triggerHaptic();"
                    type="button"
                    class="p-1.5 rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Body (scrollable) --}}
        <div class="flex-1 overflow-y-auto overscroll-contain p-4">
            {{ $slot }}
        </div>

        {{-- Footer with Apply Button --}}
        @if(isset($footer))
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
