{{--
    PWA Floating Action Button Component
    Native-style FAB with hide-on-scroll behavior

    @props
    - icon: string - SVG icon content
    - href: string - Link URL (optional)
    - hideOnScroll: bool - Hide when scrolling down
--}}

@props([
    'icon' => null,
    'href' => null,
    'hideOnScroll' => true,
])

@php
$tag = $href ? 'a' : 'button';
@endphp

<div
    x-data="{
        visible: true,
        lastScrollY: 0,
        hideOnScroll: {{ $hideOnScroll ? 'true' : 'false' }},

        init() {
            if (!this.hideOnScroll) return;

            let ticking = false;
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        this.handleScroll();
                        ticking = false;
                    });
                    ticking = true;
                }
            }, { passive: true });
        },

        handleScroll() {
            const currentScrollY = window.scrollY;
            const delta = currentScrollY - this.lastScrollY;

            // Scrolling down more than 10px
            if (delta > 10 && currentScrollY > 100) {
                this.visible = false;
            }
            // Scrolling up more than 10px
            else if (delta < -10) {
                this.visible = true;
            }

            this.lastScrollY = currentScrollY;
        },

        handleClick() {
            // Haptic feedback
            if (window.SmHaptic) {
                window.SmHaptic.medium();
            } else if (navigator.vibrate) {
                navigator.vibrate(15);
            }
        }
    }"
    x-show="visible"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-75 translate-y-4"
    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
    x-transition:leave-end="opacity-0 scale-75 translate-y-4"
    class="fixed z-40"
    style="bottom: calc(72px + env(safe-area-inset-bottom, 0px)); right: 16px;"
>
    <{{ $tag }}
        @if($href) href="{{ $href }}" @endif
        @click="handleClick()"
        {{ $attributes->merge([
            'class' => 'flex items-center justify-center w-14 h-14 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-full shadow-lg active:scale-95 transition-all duration-150'
        ]) }}
        style="-webkit-tap-highlight-color: transparent;"
    >
        @if($icon)
            {!! $icon !!}
        @else
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        @endif
    </{{ $tag }}>
</div>
