

<div x-data="offlineIndicator()"
     x-show="!isOnline"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-y-full"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-full"
     class="offline-indicator fixed top-0 left-0 right-0 z-[200] bg-gradient-to-r from-red-600 to-red-500 text-white text-center py-3 px-4 shadow-lg"
     style="margin-top: env(safe-area-inset-top, 0px);">

    <div class="flex items-center justify-center space-x-2">
        
        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"/>
        </svg>

        
        <span class="text-sm font-medium">
            <span x-show="!reconnecting">Нет подключения к интернету</span>
            <span x-show="reconnecting" class="flex items-center space-x-2">
                <span>Переподключение</span>
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </span>
        </span>
    </div>

</div>


<div x-data="{ justOnline: false }"
     @connection-restored.window="justOnline = true; setTimeout(() => justOnline = false, 3000)"
     x-show="justOnline"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-y-full"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-500"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-full"
     class="fixed top-0 left-0 right-0 z-[200] bg-gradient-to-r from-green-600 to-green-500 text-white text-center py-3 px-4 shadow-lg"
     style="margin-top: env(safe-area-inset-top, 0px);">

    <div class="flex items-center justify-center space-x-2">
        
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>

        
        <span class="text-sm font-medium">Подключение восстановлено</span>
    </div>

</div>

<script>
function offlineIndicator() {
    return {
        isOnline: navigator.onLine,
        reconnecting: false,
        reconnectAttempts: 0,
        reconnectInterval: null,

        init() {
            // Listen to online/offline events
            window.addEventListener('online', () => this.handleOnline());
            window.addEventListener('offline', () => this.handleOffline());

            // Periodic connectivity check
            this.startConnectivityCheck();
        },

        handleOffline() {
            this.isOnline = false;
            this.reconnecting = true;

            // Haptic feedback
            if (window.haptic) {
                window.haptic.error();
            }

            console.warn('📴 Network: Offline');

            // Start reconnection attempts
            this.startReconnection();
        },

        handleOnline() {
            this.isOnline = true;
            this.reconnecting = false;
            this.reconnectAttempts = 0;

            // Stop reconnection attempts
            if (this.reconnectInterval) {
                clearInterval(this.reconnectInterval);
                this.reconnectInterval = null;
            }

            // Haptic feedback
            if (window.haptic) {
                window.haptic.success();
            }

            console.log('✅ Network: Back online');

            // Dispatch event for other components
            window.dispatchEvent(new CustomEvent('connection-restored'));

            // Reload failed requests or refresh data
            this.refreshData();
        },

        startReconnection() {
            // Try to reconnect every 5 seconds
            this.reconnectInterval = setInterval(() => {
                this.checkConnection();
                this.reconnectAttempts++;

                if (this.reconnectAttempts > 20) {
                    // Stop trying after 20 attempts (100 seconds)
                    clearInterval(this.reconnectInterval);
                    this.reconnecting = false;
                }
            }, 5000);
        },

        async checkConnection() {
            try {
                // Try to fetch a small resource
                const response = await fetch('/api/health-check', {
                    method: 'HEAD',
                    cache: 'no-cache'
                });

                if (response.ok) {
                    // Connection restored
                    this.handleOnline();
                }
            } catch (error) {
                // Still offline
                console.log('Still offline, attempt:', this.reconnectAttempts);
            }
        },

        startConnectivityCheck() {
            // Check connectivity every 30 seconds
            setInterval(() => {
                if (!navigator.onLine && this.isOnline) {
                    // Browser says offline but we think we're online
                    this.handleOffline();
                } else if (navigator.onLine && !this.isOnline) {
                    // Browser says online but we think we're offline
                    this.checkConnection();
                }
            }, 30000);
        },

        refreshData() {
            // Refresh current page data after reconnection
            // This can be customized per page
            if (typeof Alpine !== 'undefined' && Alpine.store('auth')) {
                const currentCompany = Alpine.store('auth').currentCompany;
                if (currentCompany) {
                    // Reload company data if available
                    Alpine.store('auth').loadCompanies();
                }
            }
        }
    };
}
</script>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\components\offline-indicator.blade.php ENDPATH**/ ?>