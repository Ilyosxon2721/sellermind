{{-- Offline Indicator - Shows when internet connection is lost --}}

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
        {{-- Offline icon --}}
        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"/>
        </svg>

        {{-- Message --}}
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

{{-- Online indicator (brief flash when back online) --}}
<div x-data="{ justOnline: false, syncingCount: 0 }"
     @connection-restored.window="justOnline = true; setTimeout(() => justOnline = false, 3000)"
     x-init="
         window.addEventListener('sm:sync-queue-updated', (e) => {
             syncingCount = e.detail.count;
         });
     "
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
        {{-- Online icon --}}
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>

        {{-- Message --}}
        <span class="text-sm font-medium">
            <span x-show="syncingCount === 0">Подключение восстановлено</span>
            <span x-show="syncingCount > 0">Синхронизация <span x-text="syncingCount"></span> действий...</span>
        </span>
    </div>

</div>

{{-- Sync Queue Indicator (shows when there are pending actions) --}}
<div x-data="syncQueueIndicator()"
     x-show="pendingCount > 0 && !isExpanded"
     x-transition
     @click="isExpanded = true"
     class="fixed bottom-24 right-4 z-[150] bg-blue-600 text-white rounded-full p-3 shadow-lg cursor-pointer hover:bg-blue-700 transition-colors pwa-only">

    <div class="relative">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center"
              x-text="pendingCount"></span>
    </div>
</div>

{{-- Expanded Sync Queue Panel --}}
<div x-data="syncQueueIndicator()"
     x-show="isExpanded"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     @click.away="isExpanded = false"
     class="fixed bottom-24 right-4 z-[150] bg-white rounded-2xl shadow-xl w-80 max-h-96 overflow-hidden pwa-only">

    {{-- Header --}}
    <div class="bg-blue-600 text-white px-4 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span class="font-semibold">Очередь синхронизации</span>
        </div>
        <button @click="isExpanded = false" class="p-1 hover:bg-blue-700 rounded-full transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Content --}}
    <div class="p-4 max-h-64 overflow-y-auto">
        <template x-if="pendingActions.length === 0">
            <div class="text-center text-gray-500 py-4">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm">Все действия синхронизированы</p>
            </div>
        </template>

        <template x-for="action in pendingActions" :key="action.id">
            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate" x-text="action.description || action.type"></p>
                    <p class="text-xs text-gray-500" x-text="formatTime(action.timestamp)"></p>
                    <template x-if="action.retryCount > 0">
                        <p class="text-xs text-yellow-600">Попытка <span x-text="action.retryCount + 1"></span> из <span x-text="action.maxRetries"></span></p>
                    </template>
                </div>
                <button @click="cancelAction(action.id)"
                        class="ml-2 p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors"
                        title="Отменить">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Footer --}}
    <div class="border-t border-gray-100 px-4 py-3 bg-gray-50 flex items-center justify-between">
        <span class="text-sm text-gray-600">
            <span x-text="pendingCount"></span> в очереди
        </span>
        <div class="flex space-x-2">
            <button @click="clearAll()"
                    x-show="pendingCount > 0"
                    class="px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                Очистить
            </button>
            <button @click="syncNow()"
                    x-show="pendingCount > 0"
                    :disabled="!isOnline"
                    class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors">
                Синхронизировать
            </button>
        </div>
    </div>
</div>

<script>
function syncQueueIndicator() {
    return {
        pendingCount: 0,
        pendingActions: [],
        isExpanded: false,
        isOnline: navigator.onLine,

        init() {
            // Загружаем начальное состояние
            this.loadPendingActions();

            // Слушаем обновления очереди
            window.addEventListener('sm:sync-queue-updated', (e) => {
                this.pendingCount = e.detail.count;
                this.loadPendingActions();
            });

            // Слушаем статус соединения
            window.addEventListener('online', () => {
                this.isOnline = true;
            });
            window.addEventListener('offline', () => {
                this.isOnline = false;
            });
        },

        async loadPendingActions() {
            if (window.SmBackgroundSync) {
                try {
                    this.pendingActions = await window.SmBackgroundSync.getPendingActions();
                    this.pendingCount = this.pendingActions.length;
                } catch (e) {
                    console.warn('Failed to load pending actions:', e);
                }
            }
        },

        formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;

            if (diff < 60000) return 'Только что';
            if (diff < 3600000) return Math.floor(diff / 60000) + ' мин. назад';
            if (diff < 86400000) return Math.floor(diff / 3600000) + ' ч. назад';
            return date.toLocaleDateString('ru-RU');
        },

        async cancelAction(id) {
            if (window.SmOffline) {
                await window.SmOffline.cancelPendingAction(id);
                await this.loadPendingActions();

                if (window.haptic) {
                    window.haptic.light();
                }
            }
        },

        async clearAll() {
            if (confirm('Отменить все ожидающие действия?')) {
                if (window.SmBackgroundSync) {
                    await window.SmBackgroundSync.clearQueue();
                    await this.loadPendingActions();

                    if (window.haptic) {
                        window.haptic.medium();
                    }
                }
            }
        },

        async syncNow() {
            if (window.SmOffline && this.isOnline) {
                await window.SmOffline.syncNow();

                if (window.haptic) {
                    window.haptic.success();
                }
            }
        }
    };
}
</script>

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

            // Синхронизируем отложенные действия через Background Sync
            if (window.SmBackgroundSync) {
                window.SmBackgroundSync.processQueue();
            }
        }
    };
}
</script>
