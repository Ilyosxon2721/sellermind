


<nav x-data="dockNav()"
     x-show="shouldShow"
     x-cloak
     class="dock-nav"
     :class="{
         'dock-bottom': position === 'bottom',
         'dock-top': position === 'top',
         'dock-pwa': isPWA,
         'dock-desktop': !isPWA
     }">

    
    <div x-show="!isPWA" class="dock-container">
        <div class="dock-items">
            
            <a href="/home" class="dock-item" :class="{ 'active': isActive('/home') || isActive('/dashboard') }"
               @mouseenter="hoverItem($event)" @mouseleave="unhoverItems()">
                <div class="dock-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
                <span class="dock-label"><?php echo e(__('app.nav.dashboard')); ?></span>
            </a>

            
            <a href="/products" class="dock-item" :class="{ 'active': isActive('/products') }"
               @mouseenter="hoverItem($event)" @mouseleave="unhoverItems()">
                <div class="dock-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <span class="dock-label"><?php echo e(__('app.nav.products')); ?></span>
            </a>

            
            <a href="/sales" class="dock-item" :class="{ 'active': isActive('/sales') }"
               @mouseenter="hoverItem($event)" @mouseleave="unhoverItems()">
                <div class="dock-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <span class="dock-label"><?php echo e(__('app.nav.sales')); ?></span>
            </a>

            
            <a href="/marketplace" class="dock-item" :class="{ 'active': isActive('/marketplace') }"
               @mouseenter="hoverItem($event)" @mouseleave="unhoverItems()">
                <div class="dock-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="dock-label"><?php echo e(__('app.nav.marketplace')); ?></span>
            </a>

            <div class="dock-divider"></div>

            
            <a href="/warehouse" class="dock-item" :class="{ 'active': isActive('/warehouse') }"
               @mouseenter="hoverItem($event)" @mouseleave="unhoverItems()">
                <div class="dock-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                    </svg>
                </div>
                <span class="dock-label"><?php echo e(__('app.nav.warehouse')); ?></span>
            </a>

            
            <a href="/counterparties" class="dock-item" :class="{ 'active': isActive('/counterparties') }"
               @mouseenter="hoverItem($event)" @mouseleave="unhoverItems()">
                <div class="dock-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="dock-label"><?php echo e(__('admin.counterparties')); ?></span>
            </a>

            
            <a href="/finance" class="dock-item" :class="{ 'active': isActive('/finance') }"
               @mouseenter="hoverItem($event)" @mouseleave="unhoverItems()">
                <div class="dock-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="dock-label"><?php echo e(__('app.nav.finance')); ?></span>
            </a>

            <div class="dock-divider"></div>

            
            <a href="/settings" class="dock-item" :class="{ 'active': isActive('/settings') }"
               @mouseenter="hoverItem($event)" @mouseleave="unhoverItems()">
                <div class="dock-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="dock-label"><?php echo e(__('app.nav.settings')); ?></span>
            </a>
        </div>
    </div>

    
    <div x-show="isPWA" class="pwa-tabs-container">
        <a href="/home" class="pwa-tab-item" :class="{ 'active': isActive('/home') || isActive('/dashboard') }">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span><?php echo e(__('app.nav.dashboard')); ?></span>
        </a>

        <a href="/products" class="pwa-tab-item" :class="{ 'active': isActive('/products') }">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span><?php echo e(__('app.nav.products')); ?></span>
        </a>

        <a href="/sales" class="pwa-tab-item" :class="{ 'active': isActive('/sales') }">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <span><?php echo e(__('app.nav.sales')); ?></span>
        </a>

        <a href="/marketplace" class="pwa-tab-item" :class="{ 'active': isActive('/marketplace') }">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span><?php echo e(__('app.nav.marketplace')); ?></span>
        </a>

        <button @click="toggleMoreMenu()" class="pwa-tab-item" :class="{ 'active': moreMenuOpen || isMoreActive() }">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/>
            </svg>
            <span><?php echo e(__('app.settings.navigation.more')); ?></span>
        </button>

        
        <div x-show="moreMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             @click.away="moreMenuOpen = false"
             class="pwa-more-menu"
             :class="position === 'top' ? 'top-full mt-2' : 'bottom-full mb-2'">
            <a href="/warehouse" @click="closeMoreMenu()" class="pwa-more-item" :class="{ 'active': isActive('/warehouse') }">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                </svg>
                <span><?php echo e(__('app.nav.warehouse')); ?></span>
            </a>
            <a href="/counterparties" @click="closeMoreMenu()" class="pwa-more-item" :class="{ 'active': isActive('/counterparties') }">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span><?php echo e(__('admin.counterparties')); ?></span>
            </a>
            <a href="/inventory" @click="closeMoreMenu()" class="pwa-more-item" :class="{ 'active': isActive('/inventory') }">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <span><?php echo e(__('admin.inventory')); ?></span>
            </a>
            <a href="/finance" @click="closeMoreMenu()" class="pwa-more-item" :class="{ 'active': isActive('/finance') }">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span><?php echo e(__('app.nav.finance')); ?></span>
            </a>
            <div class="pwa-more-divider"></div>
            <a href="/settings" @click="closeMoreMenu()" class="pwa-more-item" :class="{ 'active': isActive('/settings') }">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span><?php echo e(__('app.nav.settings')); ?></span>
            </a>
        </div>
    </div>
</nav>

<script>
function dockNav() {
    return {
        currentPath: window.location.pathname,
        moreMenuOpen: false,
        isPWA: false,
        position: 'left',

        init() {
            this.isPWA = window.matchMedia('(display-mode: standalone)').matches ||
                         window.navigator.standalone ||
                         document.referrer.includes('android-app://');

            // Set initial position from store
            if (typeof Alpine !== 'undefined' && Alpine.store('ui')) {
                this.position = Alpine.store('ui').navPosition || 'left';
            }

            // Watch for position changes
            this.$watch('$store.ui.navPosition', (val) => {
                this.position = val;
            });

            window.addEventListener('popstate', () => {
                this.currentPath = window.location.pathname;
            });
        },

        get shouldShow() {
            // Check store directly - Alpine will make this reactive
            if (typeof Alpine !== 'undefined' && Alpine.store('ui')) {
                const navPosition = Alpine.store('ui').navPosition;
                return this.isPWA || navPosition === 'bottom' || navPosition === 'top';
            }
            // Fallback during initial load
            return this.isPWA;
        },

        isActive(path) {
            if (path === '/home' || path === '/dashboard') {
                return this.currentPath === '/home' || this.currentPath === '/dashboard' || this.currentPath === '/';
            }
            return this.currentPath.startsWith(path);
        },

        isMoreActive() {
            const morePaths = ['/warehouse', '/counterparties', '/inventory', '/finance', '/settings'];
            return morePaths.some(path => this.currentPath.startsWith(path));
        },

        toggleMoreMenu() {
            this.moreMenuOpen = !this.moreMenuOpen;
            if (window.haptic) window.haptic.selection();
        },

        closeMoreMenu() {
            this.moreMenuOpen = false;
        },

        hoverItem(event) {
            const items = event.target.closest('.dock-items').querySelectorAll('.dock-item');
            const hoveredItem = event.target.closest('.dock-item');
            const hoveredIndex = Array.from(items).indexOf(hoveredItem);

            items.forEach((item, index) => {
                const distance = Math.abs(index - hoveredIndex);
                let scale = 1;
                let translateY = 0;

                if (distance === 0) {
                    scale = 1.4;
                    translateY = this.position === 'top' ? 8 : -8;
                } else if (distance === 1) {
                    scale = 1.2;
                    translateY = this.position === 'top' ? 4 : -4;
                } else if (distance === 2) {
                    scale = 1.05;
                    translateY = this.position === 'top' ? 2 : -2;
                }

                item.style.transform = `scale(${scale}) translateY(${translateY}px)`;
            });
        },

        unhoverItems() {
            const items = document.querySelectorAll('.dock-item');
            items.forEach(item => {
                item.style.transform = 'scale(1) translateY(0)';
            });
        }
    };
}
</script>

<style>
/* Base Dock Nav */
.dock-nav {
    position: fixed;
    left: 0;
    right: 0;
    z-index: 50;
    display: flex;
    justify-content: center;
    padding: 8px 16px;
}

.dock-nav.dock-bottom {
    bottom: 0;
    padding-bottom: calc(8px + env(safe-area-inset-bottom, 0px));
}

.dock-nav.dock-top {
    top: 0;
    padding-top: calc(8px + env(safe-area-inset-top, 0px));
}

/* Desktop Dock Container - macOS Style */
.dock-container {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 8px 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12),
                0 2px 8px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.dock-items {
    display: flex;
    align-items: flex-end;
    gap: 4px;
    height: 64px;
}

.dock-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    padding: 6px 10px;
    text-decoration: none;
    color: #64748b;
    transition: transform 0.15s cubic-bezier(0.34, 1.56, 0.64, 1),
                color 0.15s ease;
    transform-origin: bottom center;
    cursor: pointer;
}

.dock-top .dock-item {
    justify-content: flex-start;
    transform-origin: top center;
}

.dock-item:hover {
    color: #3b82f6;
}

.dock-item.active {
    color: #3b82f6;
}

.dock-item.active::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    background: #3b82f6;
    border-radius: 50%;
}

.dock-top .dock-item.active::after {
    bottom: auto;
    top: 2px;
}

.dock-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
    transition: all 0.15s ease;
}

.dock-item:hover .dock-icon,
.dock-item.active .dock-icon {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.dock-icon svg {
    width: 22px;
    height: 22px;
}

.dock-label {
    font-size: 10px;
    font-weight: 500;
    margin-top: 4px;
    white-space: nowrap;
    opacity: 0;
    transform: translateY(4px);
    transition: all 0.15s ease;
}

.dock-item:hover .dock-label {
    opacity: 1;
    transform: translateY(0);
}

.dock-divider {
    width: 1px;
    height: 40px;
    background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.1), transparent);
    margin: 0 8px;
    align-self: center;
}

/* PWA Compact Mode */
.pwa-tabs-container {
    display: flex;
    align-items: center;
    justify-content: space-around;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 0;
    width: 100%;
    height: 56px;
    border-top: 1px solid rgba(0, 0, 0, 0.08);
    position: relative;
}

.dock-top .pwa-tabs-container {
    border-top: none;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.pwa-tab-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    height: 100%;
    padding: 4px 0;
    text-decoration: none;
    color: #9ca3af;
    background: none;
    border: none;
    cursor: pointer;
    transition: color 0.15s ease, transform 0.1s ease;
    -webkit-tap-highlight-color: transparent;
}

.pwa-tab-item:active {
    transform: scale(0.92);
}

.pwa-tab-item.active {
    color: #3b82f6;
}

.pwa-tab-item svg {
    margin-bottom: 2px;
}

.pwa-tab-item span {
    font-size: 10px;
    font-weight: 500;
}

/* PWA More Menu */
.pwa-more-menu {
    position: absolute;
    right: 8px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    min-width: 200px;
    padding: 8px 0;
    z-index: 100;
}

.pwa-more-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #374151;
    text-decoration: none;
    transition: background-color 0.15s;
}

.pwa-more-item:hover {
    background-color: #f3f4f6;
}

.pwa-more-item.active {
    color: #3b82f6;
    background-color: #eff6ff;
}

.pwa-more-item span {
    font-size: 14px;
    font-weight: 500;
}

.pwa-more-divider {
    height: 1px;
    background: #e5e7eb;
    margin: 8px 0;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .dock-container {
        background: rgba(30, 41, 59, 0.9);
        border-color: rgba(255, 255, 255, 0.1);
    }

    .dock-item {
        color: #94a3b8;
    }

    .dock-icon {
        background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
    }

    .dock-item:hover .dock-icon,
    .dock-item.active .dock-icon {
        background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
    }
}
</style>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views/components/bottom-tab-nav.blade.php ENDPATH**/ ?>