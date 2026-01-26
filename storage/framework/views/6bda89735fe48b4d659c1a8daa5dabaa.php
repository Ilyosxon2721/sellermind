


<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => 'SellerMind',
    'subtitle' => null,
    'showBack' => false,
    'backUrl' => null,
    'actions' => null
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'title' => 'SellerMind',
    'subtitle' => null,
    'showBack' => false,
    'backUrl' => null,
    'actions' => null
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<nav x-data="pwaTopNavbar()"
     x-show="isPWA"
     x-cloak
     class="fixed top-0 left-0 right-0 z-40 lg:hidden safe-area-top">

    
    <div class="h-safe-top bg-gradient-to-r from-blue-600 to-blue-700"></div>

    
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
        <div class="flex items-center justify-between px-4 h-14">

            
            <div class="flex items-center space-x-3 min-w-0">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showBack && $backUrl): ?>
                    <a href="<?php echo e($backUrl); ?>"
                       @click="hapticFeedback()"
                       class="p-2 -ml-2 hover:bg-white/10 rounded-lg transition-colors active:scale-95">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                <?php else: ?>
                    
                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5zm0 2.18l8 4V17c0 4.52-3.03 8.77-7.5 10-4.47-1.23-7.5-5.48-7.5-10V8.18l7-4z"/>
                        </svg>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <div class="min-w-0 flex-1">
                    <h1 class="text-base font-semibold truncate"><?php echo e($title); ?></h1>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($subtitle): ?>
                        <p class="text-xs text-white/80 truncate"><?php echo e($subtitle); ?></p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            
            <div class="flex items-center space-x-2">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($actions): ?>
                    <?php echo e($actions); ?>

                <?php else: ?>
                    
                    <button @click="showNotifications = !showNotifications; hapticFeedback()"
                            class="relative p-2 hover:bg-white/10 rounded-lg transition-colors active:scale-95">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        
                        <span x-show="notificationCount > 0"
                              class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full border border-blue-600"></span>
                    </button>

                    
                    <button @click="showSearch = !showSearch; hapticFeedback()"
                            class="p-2 hover:bg-white/10 rounded-lg transition-colors active:scale-95">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

        </div>

        
        <div x-show="showSearch"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="px-4 pb-3">
            <div class="relative">
                <input type="search"
                       x-ref="searchInput"
                       x-init="$watch('showSearch', value => value && setTimeout(() => $refs.searchInput.focus(), 100))"
                       placeholder="Поиск..."
                       class="w-full pl-10 pr-4 py-2 bg-white/20 border border-white/30 rounded-xl text-white placeholder-white/70 focus:bg-white/30 focus:outline-none focus:ring-2 focus:ring-white/50 backdrop-blur-sm">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    </div>

    
    <div x-show="showNotifications"
         @click.away="showNotifications = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute top-full right-0 left-0 mx-4 mt-2 bg-white rounded-xl shadow-2xl overflow-hidden max-h-96 overflow-y-auto">

        
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">Уведомления</h3>
                <button @click="markAllRead()" class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                    Отметить все
                </button>
            </div>
        </div>

        
        <div class="divide-y divide-gray-100">
            <template x-for="notification in notifications" :key="notification.id">
                <div class="px-4 py-3 hover:bg-gray-50 transition-colors cursor-pointer"
                     :class="!notification.read ? 'bg-blue-50' : ''"
                     @click="markAsRead(notification.id)">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900" x-text="notification.title"></p>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="notification.message"></p>
                            <p class="text-xs text-gray-400 mt-1" x-text="notification.time"></p>
                        </div>
                    </div>
                </div>
            </template>

            
            <div x-show="notifications.length === 0" class="px-4 py-8 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <p class="text-sm text-gray-500 mt-2">Нет уведомлений</p>
            </div>
        </div>
    </div>

</nav>

<script>
function pwaTopNavbar() {
    return {
        isPWA: window.isPWAInstalled || false,
        showNotifications: false,
        showSearch: false,
        notificationCount: 0,
        notifications: [
            // Example notifications
            // { id: 1, title: 'Новый заказ', message: 'Заказ #12345 получен', time: '5 мин назад', read: false }
        ],

        init() {
            // Listen for PWA mode changes
            this.$watch('$store.pwa.isInstalled', (value) => {
                this.isPWA = value;
            });

            // Load notifications
            this.loadNotifications();
        },

        async loadNotifications() {
            try {
                // TODO: Load from API
                // const response = await window.api.get('/notifications');
                // this.notifications = response.data;
                this.notificationCount = this.notifications.filter(n => !n.read).length;
            } catch (error) {
                console.error('Failed to load notifications:', error);
            }
        },

        markAsRead(notificationId) {
            const notification = this.notifications.find(n => n.id === notificationId);
            if (notification && !notification.read) {
                notification.read = true;
                this.notificationCount = Math.max(0, this.notificationCount - 1);
                this.hapticFeedback();

                // TODO: Mark as read on server
                // window.api.post(`/notifications/${notificationId}/read`);
            }
        },

        markAllRead() {
            this.notifications.forEach(n => n.read = true);
            this.notificationCount = 0;
            this.hapticFeedback();

            // TODO: Mark all as read on server
            // window.api.post('/notifications/mark-all-read');
        },

        hapticFeedback() {
            // Use global haptic system
            if (window.haptic) {
                window.haptic.light();
            }
        }
    };
}
</script>

<style>
/* Safe area for iOS notch */
.safe-area-top {
    padding-top: env(safe-area-inset-top);
}

.h-safe-top {
    height: env(safe-area-inset-top);
}

/* iOS-style backdrop blur effect */
@supports ((-webkit-backdrop-filter: blur(10px)) or (backdrop-filter: blur(10px))) {
    .backdrop-blur-sm {
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
    }
}

/* Smooth transitions */
button {
    -webkit-tap-highlight-color: transparent;
    user-select: none;
}

/* Add padding to body for fixed top navbar */
@media (max-width: 1024px) {
    body.has-pwa-navbar {
        padding-top: calc(3.5rem + env(safe-area-inset-top, 0px));
    }
}
</style>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views/components/pwa-top-navbar.blade.php ENDPATH**/ ?>