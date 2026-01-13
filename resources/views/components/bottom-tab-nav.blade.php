{{-- Bottom Tab Navigation - Native Mobile App Style --}}
{{-- Shows on all mobile/tablet devices (not just PWA) --}}

<nav x-data="bottomTabNav()"
     x-show="shouldShow"
     x-cloak
     class="fixed bottom-0 left-0 right-0 z-50 lg:hidden bg-white border-t border-gray-200 safe-area-bottom">

    <div class="flex items-center justify-around h-16 px-2">

        {{-- Dashboard Tab --}}
        <a href="/dashboard"
           @click="hapticFeedback()"
           :class="isActive('/dashboard') ? 'text-blue-600' : 'text-gray-500'"
           class="flex flex-col items-center justify-center flex-1 h-full transition-all duration-200 active:scale-95">
            <div class="relative">
                <svg class="w-6 h-6 mb-1 transition-transform"
                     :class="isActive('/dashboard') ? 'scale-110' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                {{-- Active Indicator Dot --}}
                <span x-show="isActive('/dashboard')"
                      class="absolute -top-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-blue-600 rounded-full"></span>
            </div>
            <span class="text-xs font-medium" :class="isActive('/dashboard') ? 'font-semibold' : ''">
                Главная
            </span>
        </a>

        {{-- Products Tab --}}
        <a href="/products"
           @click="hapticFeedback()"
           :class="isActive('/products') ? 'text-blue-600' : 'text-gray-500'"
           class="flex flex-col items-center justify-center flex-1 h-full transition-all duration-200 active:scale-95">
            <div class="relative">
                <svg class="w-6 h-6 mb-1 transition-transform"
                     :class="isActive('/products') ? 'scale-110' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span x-show="isActive('/products')"
                      class="absolute -top-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-blue-600 rounded-full"></span>
            </div>
            <span class="text-xs font-medium" :class="isActive('/products') ? 'font-semibold' : ''">
                Товары
            </span>
        </a>

        {{-- Sales Tab --}}
        <a href="/sales"
           @click="hapticFeedback()"
           :class="isActive('/sales') ? 'text-blue-600' : 'text-gray-500'"
           class="flex flex-col items-center justify-center flex-1 h-full transition-all duration-200 active:scale-95">
            <div class="relative">
                <svg class="w-6 h-6 mb-1 transition-transform"
                     :class="isActive('/sales') ? 'scale-110' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span x-show="isActive('/sales')"
                      class="absolute -top-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-blue-600 rounded-full"></span>
            </div>
            <span class="text-xs font-medium" :class="isActive('/sales') ? 'font-semibold' : ''">
                Продажи
            </span>
        </a>

        {{-- Analytics Tab --}}
        <a href="/analytics"
           @click="hapticFeedback()"
           :class="isActive('/analytics') ? 'text-blue-600' : 'text-gray-500'"
           class="flex flex-col items-center justify-center flex-1 h-full transition-all duration-200 active:scale-95">
            <div class="relative">
                <svg class="w-6 h-6 mb-1 transition-transform"
                     :class="isActive('/analytics') ? 'scale-110' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span x-show="isActive('/analytics')"
                      class="absolute -top-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-blue-600 rounded-full"></span>
            </div>
            <span class="text-xs font-medium" :class="isActive('/analytics') ? 'font-semibold' : ''">
                Аналитика
            </span>
        </a>

        {{-- Profile/Settings Tab --}}
        <a href="/profile"
           @click="hapticFeedback()"
           :class="isActive('/profile') || isActive('/company') ? 'text-blue-600' : 'text-gray-500'"
           class="flex flex-col items-center justify-center flex-1 h-full transition-all duration-200 active:scale-95">
            <div class="relative">
                <svg class="w-6 h-6 mb-1 transition-transform"
                     :class="isActive('/profile') || isActive('/company') ? 'scale-110' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span x-show="isActive('/profile') || isActive('/company')"
                      class="absolute -top-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-blue-600 rounded-full"></span>
            </div>
            <span class="text-xs font-medium" :class="isActive('/profile') || isActive('/company') ? 'font-semibold' : ''">
                Профиль
            </span>
        </a>

    </div>
</nav>

<script>
function bottomTabNav() {
    return {
        currentPath: window.location.pathname,

        init() {
            // Update on navigation (for SPA-like behavior)
            window.addEventListener('popstate', () => {
                this.currentPath = window.location.pathname;
            });
        },

        get shouldShow() {
            // Always show on mobile (screen width < 1024px)
            // Hide on desktop (lg: breakpoint)
            return true; // CSS handles hiding on desktop with lg:hidden
        },

        isActive(path) {
            // Exact match for dashboard
            if (path === '/dashboard') {
                return this.currentPath === path || this.currentPath === '/home';
            }
            // Prefix match for other routes
            return this.currentPath.startsWith(path);
        },

        hapticFeedback() {
            // Use global haptic system
            if (window.haptic) {
                window.haptic.selection();
            }
        }
    };
}
</script>

<style>
/* Safe area for iOS notch/home indicator */
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom);
}

/* iOS-style backdrop blur effect */
@supports ((-webkit-backdrop-filter: blur(10px)) or (backdrop-filter: blur(10px))) {
    nav.bg-white {
        background-color: rgba(255, 255, 255, 0.8);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
    }
}

/* Active tab animation */
@keyframes tab-bounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Smooth transitions */
nav a {
    -webkit-tap-highlight-color: transparent;
    user-select: none;
}

/* Prevent content from being hidden behind bottom nav */
body {
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
</style>
