{{--
    PWA Top Bar Component
    Native-style navigation bar for PWA mode

    @props
    - title: string - Page title
    - showBack: bool|string - Show back button ('auto' uses navigation stack)
    - backUrl: string - Optional custom back URL
    - transparent: bool - Transparent background
    - actions: slot - Right side action buttons
--}}

@props([
    'title' => '',
    'showBack' => 'auto',
    'backUrl' => null,
    'transparent' => false,
])

@php
$bgClass = $transparent
    ? 'bg-transparent'
    : 'bg-white border-b border-gray-200';
@endphp

<header
    x-data="pwaTopBar()"
    x-cloak
    class="fixed top-0 inset-x-0 z-50 pwa-only"
    :class="{ 'opacity-0': !ready }"
>
    {{-- Safe area padding for iOS notch --}}
    <div class="h-safe-top {{ $transparent ? '' : 'bg-white' }}"></div>

    {{-- Main bar --}}
    <div class="h-12 {{ $bgClass }} flex items-center justify-between px-4">

        {{-- Left: Back button --}}
        <div class="flex items-center min-w-0 flex-1">
            <template x-if="shouldShowBack">
                <button
                    @click="goBack()"
                    class="flex items-center -ml-2 p-2 rounded-lg hover:bg-gray-100 active:bg-gray-200 transition-colors"
                    aria-label="Назад"
                >
                    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span
                        x-show="previousTitle"
                        x-text="truncateTitle(previousTitle)"
                        class="ml-1 text-blue-600 text-sm font-medium max-w-[80px] truncate"
                    ></span>
                </button>
            </template>
        </div>

        {{-- Center: Title --}}
        <div class="flex-shrink-0 px-4">
            <h1
                class="text-base font-semibold text-gray-900 text-center truncate max-w-[200px]"
                x-text="pageTitle || '{{ $title }}'"
            >{{ $title }}</h1>
        </div>

        {{-- Right: Actions slot --}}
        <div class="flex items-center justify-end min-w-0 flex-1 space-x-1">
            {{ $actions ?? '' }}
        </div>
    </div>
</header>

{{-- Spacer to prevent content from going under fixed header --}}
<div class="pwa-only h-12 mt-safe-top"></div>

<script>
function pwaTopBar() {
    return {
        ready: false,
        showBackProp: '{{ $showBack }}',
        backUrlProp: @json($backUrl),
        pageTitle: '{{ $title }}',
        previousTitle: '',

        init() {
            this.ready = true;

            // Watch navigation store for changes
            if (this.$store.navigation) {
                this.$watch('$store.navigation.stack', () => {
                    this.updateFromStack();
                });
                this.updateFromStack();
            }
        },

        get shouldShowBack() {
            // Explicit true/false
            if (this.showBackProp === 'true' || this.showBackProp === true) {
                return true;
            }
            if (this.showBackProp === 'false' || this.showBackProp === false) {
                return false;
            }

            // Auto mode - check navigation stack
            if (this.showBackProp === 'auto') {
                if (this.$store.navigation) {
                    return this.$store.navigation.canGoBack();
                }
                // Fallback: check if we have history
                return window.history.length > 1;
            }

            return false;
        },

        updateFromStack() {
            const store = this.$store.navigation;
            if (!store) return;

            const prev = store.getPreviousPage();
            this.previousTitle = prev?.title || '';
        },

        goBack() {
            // Haptic feedback
            this.hapticFeedback();

            // Use custom URL if provided
            if (this.backUrlProp) {
                window.location.href = this.backUrlProp;
                return;
            }

            // Use navigation store
            if (this.$store.navigation && this.$store.navigation.canGoBack()) {
                this.$store.navigation.pop();
                return;
            }

            // Fallback to browser history
            history.back();
        },

        truncateTitle(title) {
            if (!title) return '';
            const maxLength = 10;
            if (title.length <= maxLength) return title;
            return title.substring(0, maxLength) + '...';
        },

        hapticFeedback() {
            if (window.haptic) {
                window.haptic.light();
            } else if (window.SmHaptic) {
                window.SmHaptic.light();
            } else if (navigator.vibrate) {
                navigator.vibrate(10);
            }
        }
    };
}
</script>

<style>
/* Safe area CSS variables for iOS */
.h-safe-top {
    height: env(safe-area-inset-top, 0px);
}

.mt-safe-top {
    margin-top: env(safe-area-inset-top, 0px);
}

/* PWA only - show only in installed mode */
.pwa-only {
    display: none;
}

@media (display-mode: standalone) {
    .pwa-only {
        display: block;
    }
}

/* iOS Safari standalone mode */
@supports (-webkit-touch-callout: none) {
    @media (display-mode: standalone) {
        .pwa-only {
            display: block;
        }
    }
}

/* Back button tap state */
button:active {
    transform: scale(0.97);
}

/* Smooth transition for back button appearance */
.pwa-only button {
    transition: transform 0.1s ease-out, background-color 0.15s ease-out;
}

/* Title transition */
.pwa-only h1 {
    transition: opacity 0.2s ease-out;
}

/* Hide during x-cloak initialization */
[x-cloak] {
    display: none !important;
}
</style>
