{{-- Bottom Tab Navigation - Native Mobile App Style --}}
{{-- Shows on all mobile/tablet devices (not just PWA) --}}

<nav x-data="bottomTabNav()"
     x-show="shouldShow"
     x-cloak
     class="pwa-only fixed bottom-0 left-0 right-0 z-50 native-bottom-tabs">

    <div class="native-tabs-container">
        {{-- Dashboard Tab --}}
        <a href="/dashboard"
           @click="hapticFeedback()"
           :class="isActive('/dashboard') ? 'active' : ''"
           class="native-tab-item">
            <div class="native-tab-icon">
                <svg :class="isActive('/dashboard') ? 'text-blue-600' : 'text-gray-400'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </div>
            <span class="native-tab-label" :class="isActive('/dashboard') ? 'text-blue-600' : 'text-gray-500'">Главная</span>
        </a>

        {{-- Products Tab --}}
        <a href="/products"
           @click="hapticFeedback()"
           :class="isActive('/products') ? 'active' : ''"
           class="native-tab-item">
            <div class="native-tab-icon">
                <svg :class="isActive('/products') ? 'text-blue-600' : 'text-gray-400'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <span class="native-tab-label" :class="isActive('/products') ? 'text-blue-600' : 'text-gray-500'">Товары</span>
        </a>

        {{-- Sales Tab --}}
        <a href="/sales"
           @click="hapticFeedback()"
           :class="isActive('/sales') ? 'active' : ''"
           class="native-tab-item">
            <div class="native-tab-icon">
                <svg :class="isActive('/sales') ? 'text-blue-600' : 'text-gray-400'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <span class="native-tab-label" :class="isActive('/sales') ? 'text-blue-600' : 'text-gray-500'">Продажи</span>
        </a>

        {{-- Analytics Tab --}}
        <a href="/analytics"
           @click="hapticFeedback()"
           :class="isActive('/analytics') ? 'active' : ''"
           class="native-tab-item">
            <div class="native-tab-icon">
                <svg :class="isActive('/analytics') ? 'text-blue-600' : 'text-gray-400'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <span class="native-tab-label" :class="isActive('/analytics') ? 'text-blue-600' : 'text-gray-500'">Аналитика</span>
        </a>

        {{-- Profile/Settings Tab --}}
        <a href="/settings"
           @click="hapticFeedback()"
           :class="isActive('/settings') || isActive('/profile') ? 'active' : ''"
           class="native-tab-item">
            <div class="native-tab-icon">
                <svg :class="isActive('/settings') || isActive('/profile') ? 'text-blue-600' : 'text-gray-400'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <span class="native-tab-label" :class="isActive('/settings') || isActive('/profile') ? 'text-blue-600' : 'text-gray-500'">Профиль</span>
        </a>
    </div>
</nav>

<script>
function bottomTabNav() {
    return {
        currentPath: window.location.pathname,

        init() {
            window.addEventListener('popstate', () => {
                this.currentPath = window.location.pathname;
            });
        },

        get shouldShow() {
            return true;
        },

        isActive(path) {
            if (path === '/dashboard') {
                return this.currentPath === path || this.currentPath === '/home' || this.currentPath === '/';
            }
            return this.currentPath.startsWith(path);
        },

        hapticFeedback() {
            if (window.haptic) {
                window.haptic.selection();
            }
        }
    };
}
</script>

<style>
/* Native Bottom Tab Bar */
.pwa-mode .native-bottom-tabs {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 0.5px solid rgba(0, 0, 0, 0.1);
    padding-bottom: env(safe-area-inset-bottom, 0px);
}

.pwa-mode .native-tabs-container {
    display: flex;
    align-items: center;
    justify-content: space-around;
    height: 50px;
    padding: 0 calc(4px + env(safe-area-inset-right, 0px)) 0 calc(4px + env(safe-area-inset-left, 0px));
}

.pwa-mode .native-tab-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    height: 100%;
    padding: 4px 0;
    text-decoration: none;
    -webkit-tap-highlight-color: transparent;
    transition: transform 0.15s ease;
}

.pwa-mode .native-tab-item:active {
    transform: scale(0.92);
}

.pwa-mode .native-tab-item.active .native-tab-icon svg {
    transform: scale(1.1);
}

.pwa-mode .native-tab-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 24px;
    margin-bottom: 2px;
}

.pwa-mode .native-tab-icon svg {
    transition: transform 0.15s ease, color 0.15s ease;
}

.pwa-mode .native-tab-label {
    font-size: 10px;
    font-weight: 500;
    line-height: 1.2;
    text-align: center;
    white-space: nowrap;
}
</style>
