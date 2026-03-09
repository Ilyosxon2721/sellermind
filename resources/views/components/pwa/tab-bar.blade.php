{{--
    PWA Tab Bar Component

    5 tabs: Home, Products, Chat, Analytics, Profile
    Height: 56px + safe-area-inset-bottom
    Fixed at bottom of screen
--}}

@props([
    'badges' => [],
])

<nav
    x-data="pwaTabBarNav()"
    x-cloak
    role="navigation"
    aria-label="Osnovnaia navigatsiia"
    class="fixed inset-x-0 bottom-0 z-50 bg-white"
    style="box-shadow: 0 -1px 3px rgba(0,0,0,0.1); padding-bottom: env(safe-area-inset-bottom, 0px);"
>
    <div class="flex h-14 items-center justify-around">
        {{-- Tab: Home --}}
        <a
            href="/dashboard"
            class="group relative flex flex-1 flex-col items-center justify-center py-1 no-underline transition-colors duration-150"
            :class="activeTab === 'home' ? 'text-blue-600' : 'text-gray-500'"
            :aria-current="activeTab === 'home' ? 'page' : false"
            @click="handleTap($event, 'home')"
            style="-webkit-tap-highlight-color: transparent;"
        >
            {{-- Badge --}}
            @if(isset($badges['home']) && $badges['home'] > 0)
                <span class="absolute right-1/4 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                    {{ $badges['home'] > 99 ? '99+' : $badges['home'] }}
                </span>
            @elseif(isset($badges['home']) && $badges['home'] === true)
                <span class="absolute right-1/4 top-1 h-2 w-2 rounded-full bg-red-500"></span>
            @endif

            {{-- Icon: Outline (inactive) --}}
            <svg
                x-show="activeTab !== 'home'"
                class="h-6 w-6 transition-transform duration-150 group-active:scale-95"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>

            {{-- Icon: Solid (active) --}}
            <svg
                x-show="activeTab === 'home'"
                x-cloak
                class="h-6 w-6 transition-transform duration-150 group-active:scale-95"
                fill="currentColor"
                viewBox="0 0 24 24"
            >
                <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
                <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
            </svg>

            <span class="mt-0.5 text-[10px] font-medium leading-tight">{{ __('admin.home') }}</span>
        </a>

        {{-- Tab: Products --}}
        <a
            href="/products"
            class="group relative flex flex-1 flex-col items-center justify-center py-1 no-underline transition-colors duration-150"
            :class="activeTab === 'products' ? 'text-blue-600' : 'text-gray-500'"
            :aria-current="activeTab === 'products' ? 'page' : false"
            @click="handleTap($event, 'products')"
            style="-webkit-tap-highlight-color: transparent;"
        >
            {{-- Badge --}}
            @if(isset($badges['products']) && $badges['products'] > 0)
                <span class="absolute right-1/4 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                    {{ $badges['products'] > 99 ? '99+' : $badges['products'] }}
                </span>
            @elseif(isset($badges['products']) && $badges['products'] === true)
                <span class="absolute right-1/4 top-1 h-2 w-2 rounded-full bg-red-500"></span>
            @endif

            {{-- Icon: Outline (inactive) --}}
            <svg
                x-show="activeTab !== 'products'"
                class="h-6 w-6 transition-transform duration-150 group-active:scale-95"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
            </svg>

            {{-- Icon: Solid (active) --}}
            <svg
                x-show="activeTab === 'products'"
                x-cloak
                class="h-6 w-6 transition-transform duration-150 group-active:scale-95"
                fill="currentColor"
                viewBox="0 0 24 24"
            >
                <path d="M12.378 1.602a.75.75 0 0 0-.756 0L3 6.632l9 5.25 9-5.25-8.622-5.03ZM21.75 7.93l-9 5.25v9l8.628-5.032a.75.75 0 0 0 .372-.648V7.93ZM11.25 22.18v-9l-9-5.25v8.57a.75.75 0 0 0 .372.648l8.628 5.033Z" />
            </svg>

            <span class="mt-0.5 text-[10px] font-medium leading-tight">{{ __('admin.products') }}</span>
        </a>

        {{-- Tab: Chat --}}
        <a
            href="/chat"
            class="group relative flex flex-1 flex-col items-center justify-center py-1 no-underline transition-colors duration-150"
            :class="activeTab === 'chat' ? 'text-blue-600' : 'text-gray-500'"
            :aria-current="activeTab === 'chat' ? 'page' : false"
            @click="handleTap($event, 'chat')"
            style="-webkit-tap-highlight-color: transparent;"
        >
            {{-- Badge --}}
            @if(isset($badges['chat']) && $badges['chat'] > 0)
                <span class="absolute right-1/4 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                    {{ $badges['chat'] > 99 ? '99+' : $badges['chat'] }}
                </span>
            @elseif(isset($badges['chat']) && $badges['chat'] === true)
                <span class="absolute right-1/4 top-1 h-2 w-2 rounded-full bg-red-500"></span>
            @endif

            {{-- Icon: Outline (inactive) --}}
            <svg
                x-show="activeTab !== 'chat'"
                class="h-6 w-6 transition-transform duration-150 group-active:scale-95"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
            </svg>

            {{-- Icon: Solid (active) --}}
            <svg
                x-show="activeTab === 'chat'"
                x-cloak
                class="h-6 w-6 transition-transform duration-150 group-active:scale-95"
                fill="currentColor"
                viewBox="0 0 24 24"
            >
                <path fill-rule="evenodd" d="M4.804 21.644A6.707 6.707 0 0 0 6 21.75a6.721 6.721 0 0 0 3.583-1.029c.774.182 1.584.279 2.417.279 5.322 0 9.75-3.97 9.75-9 0-5.03-4.428-9-9.75-9s-9.75 3.97-9.75 9c0 2.409 1.025 4.587 2.674 6.192.232.226.277.428.254.543a3.73 3.73 0 0 1-.814 1.686.75.75 0 0 0 .44 1.223ZM8.25 10.875a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25ZM10.875 12a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Zm4.875-1.125a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25Z" clip-rule="evenodd" />
            </svg>

            <span class="mt-0.5 text-[10px] font-medium leading-tight">{{ __('admin.chat') }}</span>
        </a>

        {{-- Tab: Analytics --}}
        <a
            href="/analytics"
            class="group relative flex flex-1 flex-col items-center justify-center py-1 no-underline transition-colors duration-150"
            :class="activeTab === 'analytics' ? 'text-blue-600' : 'text-gray-500'"
            :aria-current="activeTab === 'analytics' ? 'page' : false"
            @click="handleTap($event, 'analytics')"
            style="-webkit-tap-highlight-color: transparent;"
        >
            {{-- Badge --}}
            @if(isset($badges['analytics']) && $badges['analytics'] > 0)
                <span class="absolute right-1/4 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                    {{ $badges['analytics'] > 99 ? '99+' : $badges['analytics'] }}
                </span>
            @elseif(isset($badges['analytics']) && $badges['analytics'] === true)
                <span class="absolute right-1/4 top-1 h-2 w-2 rounded-full bg-red-500"></span>
            @endif

            {{-- Icon: Outline (inactive) --}}
            <svg
                x-show="activeTab !== 'analytics'"
                class="h-6 w-6 transition-transform duration-150 group-active:scale-95"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
            </svg>

            {{-- Icon: Solid (active) --}}
            <svg
                x-show="activeTab === 'analytics'"
                x-cloak
                class="h-6 w-6 transition-transform duration-150 group-active:scale-95"
                fill="currentColor"
                viewBox="0 0 24 24"
            >
                <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75ZM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 0 1-1.875-1.875V8.625ZM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 0 1 3 19.875v-6.75Z" />
            </svg>

            <span class="mt-0.5 text-[10px] font-medium leading-tight">{{ __('admin.analytics') }}</span>
        </a>

        {{-- Tab: Profile --}}
        <a
            href="/profile"
            class="group relative flex flex-1 flex-col items-center justify-center py-1 no-underline transition-colors duration-150"
            :class="activeTab === 'profile' ? 'text-blue-600' : 'text-gray-500'"
            :aria-current="activeTab === 'profile' ? 'page' : false"
            @click="handleTap($event, 'profile')"
            style="-webkit-tap-highlight-color: transparent;"
        >
            {{-- Badge --}}
            @if(isset($badges['profile']) && $badges['profile'] > 0)
                <span class="absolute right-1/4 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                    {{ $badges['profile'] > 99 ? '99+' : $badges['profile'] }}
                </span>
            @elseif(isset($badges['profile']) && $badges['profile'] === true)
                <span class="absolute right-1/4 top-1 h-2 w-2 rounded-full bg-red-500"></span>
            @endif

            {{-- Icon: Outline (inactive) --}}
            <svg
                x-show="activeTab !== 'profile'"
                class="h-6 w-6 transition-transform duration-150 group-active:scale-95"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>

            {{-- Icon: Solid (active) --}}
            <svg
                x-show="activeTab === 'profile'"
                x-cloak
                class="h-6 w-6 transition-transform duration-150 group-active:scale-95"
                fill="currentColor"
                viewBox="0 0 24 24"
            >
                <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" />
            </svg>

            <span class="mt-0.5 text-[10px] font-medium leading-tight">{{ __('admin.profile') }}</span>
        </a>
    </div>
</nav>

<script>
function pwaTabBarNav() {
    return {
        activeTab: 'home',

        init() {
            // Determine active tab from current route
            this.activeTab = this.getActiveTabFromPath();

            // Listen for navigation changes
            window.addEventListener('popstate', () => {
                this.activeTab = this.getActiveTabFromPath();
            });
        },

        getActiveTabFromPath() {
            const path = window.location.pathname;

            // Check each tab route
            if (path === '/' || path === '/home' || path.startsWith('/dashboard')) {
                return 'home';
            }
            if (path.startsWith('/products')) {
                return 'products';
            }
            if (path.startsWith('/chat')) {
                return 'chat';
            }
            if (path.startsWith('/analytics')) {
                return 'analytics';
            }
            if (path.startsWith('/profile') || path.startsWith('/settings')) {
                return 'profile';
            }

            return 'home';
        },

        handleTap(event, tab) {
            // Trigger haptic feedback
            this.triggerHaptic();

            // Update active tab immediately for visual feedback
            this.activeTab = tab;
        },

        triggerHaptic() {
            // Try multiple haptic feedback APIs
            if (window.haptic && typeof window.haptic.light === 'function') {
                window.haptic.light();
            } else if (window.SmHaptic && typeof window.SmHaptic.light === 'function') {
                window.SmHaptic.light();
            } else if (navigator.vibrate) {
                // Fallback to Vibration API (10ms light vibration)
                navigator.vibrate(10);
            }
        }
    };
}
</script>
