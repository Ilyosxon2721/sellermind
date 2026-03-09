/**
 * SellerMind PWA - Background Sync
 * Синхронизация отложенных действий при восстановлении соединения
 *
 * Возможности:
 * - IndexedDB очередь для отложенных действий
 * - Автоматическая синхронизация при восстановлении соединения
 * - Periodic Background Sync для периодического обновления данных
 * - Приоритизация действий в очереди
 * - Retry с экспоненциальной задержкой
 * - Интеграция с Service Worker Background Sync API
 */

class SmBackgroundSync {
    constructor() {
        this.DB_NAME = 'sm-sync-queue';
        this.STORE_NAME = 'pending-actions';
        this.PERIODIC_STORE = 'periodic-sync';
        this.DB_VERSION = 2;
        this.db = null;
        this.isProcessing = false;
        this.syncInterval = null;

        // Приоритеты действий (чем меньше, тем важнее)
        this.PRIORITIES = {
            CRITICAL: 1,   // Платежи, критичные обновления
            HIGH: 2,       // Обновление цен, остатков
            NORMAL: 3,     // Обычные операции
            LOW: 4         // Аналитика, логирование
        };
    }

    /**
     * Инициализация IndexedDB
     */
    async init() {
        if (this.db) return;

        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.DB_NAME, this.DB_VERSION);

            request.onerror = () => {
                console.error('IndexedDB open error:', request.error);
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                resolve();
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Основное хранилище для pending действий
                if (!db.objectStoreNames.contains(this.STORE_NAME)) {
                    const store = db.createObjectStore(this.STORE_NAME, {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                    store.createIndex('type', 'type', { unique: false });
                    store.createIndex('timestamp', 'timestamp', { unique: false });
                    store.createIndex('priority', 'priority', { unique: false });
                }

                // Хранилище для периодической синхронизации
                if (!db.objectStoreNames.contains(this.PERIODIC_STORE)) {
                    const periodicStore = db.createObjectStore(this.PERIODIC_STORE, {
                        keyPath: 'id'
                    });
                    periodicStore.createIndex('lastSync', 'lastSync', { unique: false });
                }
            };
        });
    }

    /**
     * Добавить действие в очередь синхронизации
     * @param {Object} action - Действие для синхронизации
     * @param {string} action.type - Тип действия (price-update, stock-update, etc.)
     * @param {string} action.url - URL для запроса
     * @param {string} action.method - HTTP метод (POST, PUT, PATCH, DELETE)
     * @param {Object} action.body - Тело запроса
     * @param {string} action.description - Описание действия для уведомления
     * @param {number} action.priority - Приоритет (1=critical, 2=high, 3=normal, 4=low)
     * @returns {Promise<number>} - ID добавленного действия
     */
    async queueAction(action) {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.STORE_NAME, 'readwrite');
            const store = tx.objectStore(this.STORE_NAME);

            const actionWithMeta = {
                ...action,
                timestamp: Date.now(),
                retryCount: 0,
                maxRetries: action.maxRetries || 3,
                priority: action.priority || this.PRIORITIES.NORMAL,
                nextRetryAt: null
            };

            const request = store.add(actionWithMeta);

            request.onsuccess = () => {
                const actionId = request.result;
                this.registerSync();
                this.updatePendingBadge();
                this.notifyQueued(action);
                resolve(actionId);
            };

            request.onerror = () => {
                console.error('Queue action error:', request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Добавить действие с высоким приоритетом (обновление цен/остатков)
     */
    async queueHighPriority(action) {
        return this.queueAction({
            ...action,
            priority: this.PRIORITIES.HIGH
        });
    }

    /**
     * Добавить критическое действие (платежи и т.д.)
     */
    async queueCritical(action) {
        return this.queueAction({
            ...action,
            priority: this.PRIORITIES.CRITICAL,
            maxRetries: 5
        });
    }

    /**
     * Получить все ожидающие действия
     */
    async getPendingActions() {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.STORE_NAME, 'readonly');
            const store = tx.objectStore(this.STORE_NAME);
            const request = store.getAll();

            request.onsuccess = () => resolve(request.result || []);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Получить количество ожидающих действий
     */
    async getPendingCount() {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.STORE_NAME, 'readonly');
            const store = tx.objectStore(this.STORE_NAME);
            const request = store.count();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Удалить выполненное действие
     */
    async removeAction(id) {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.STORE_NAME, 'readwrite');
            const store = tx.objectStore(this.STORE_NAME);
            const request = store.delete(id);

            request.onsuccess = () => {
                this.updatePendingBadge();
                resolve();
            };
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Обновить действие (например, увеличить счетчик повторов)
     */
    async updateAction(id, updates) {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.STORE_NAME, 'readwrite');
            const store = tx.objectStore(this.STORE_NAME);
            const getRequest = store.get(id);

            getRequest.onsuccess = () => {
                const action = getRequest.result;
                if (!action) {
                    resolve(null);
                    return;
                }

                const updated = { ...action, ...updates };
                const putRequest = store.put(updated);

                putRequest.onsuccess = () => resolve(updated);
                putRequest.onerror = () => reject(putRequest.error);
            };

            getRequest.onerror = () => reject(getRequest.error);
        });
    }

    /**
     * Очистить все ожидающие действия
     */
    async clearQueue() {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.STORE_NAME, 'readwrite');
            const store = tx.objectStore(this.STORE_NAME);
            const request = store.clear();

            request.onsuccess = () => {
                this.updatePendingBadge();
                resolve();
            };
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Зарегистрировать Background Sync в Service Worker
     */
    async registerSync() {
        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            try {
                const registration = await navigator.serviceWorker.ready;
                await registration.sync.register('sm-background-sync');
            } catch (e) {
                console.warn('Background Sync registration failed:', e);
            }
        }
    }

    /**
     * Выполнить все ожидающие действия (с учетом приоритетов)
     */
    async processQueue() {
        if (this.isProcessing) return;
        if (!navigator.onLine) return;

        this.isProcessing = true;

        try {
            let actions = await this.getPendingActions();

            if (actions.length === 0) {
                this.isProcessing = false;
                return;
            }

            // Сортировка по приоритету (меньше = важнее), затем по времени
            actions = actions.sort((a, b) => {
                const priorityDiff = (a.priority || 3) - (b.priority || 3);
                if (priorityDiff !== 0) return priorityDiff;
                return a.timestamp - b.timestamp;
            });

            // Фильтруем действия, для которых еще не настало время retry
            const now = Date.now();
            actions = actions.filter(a => !a.nextRetryAt || a.nextRetryAt <= now);

            let successCount = 0;
            let failCount = 0;

            for (const action of actions) {
                try {
                    const success = await this.executeAction(action);

                    if (success) {
                        await this.removeAction(action.id);
                        successCount++;
                    } else {
                        await this.handleRetry(action);
                        failCount++;
                    }
                } catch (e) {
                    console.warn('Sync action error:', action, e);
                    await this.handleRetry(action);
                    failCount++;
                }
            }

            if (successCount > 0) {
                this.notifySyncComplete(successCount, failCount);
            }
        } finally {
            this.isProcessing = false;
            this.updatePendingBadge();
        }
    }

    /**
     * Выполнить одно действие
     */
    async executeAction(action) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        const response = await fetch(action.url, {
            method: action.method || 'POST',
            headers,
            body: action.body ? JSON.stringify(action.body) : undefined,
            credentials: 'same-origin'
        });

        if (response.ok) {
            this.notifySuccess(action);
            return true;
        }

        if (response.status >= 400 && response.status < 500) {
            this.notifyError(action, 'Ошибка запроса: ' + response.status);
            return true;
        }

        return false;
    }

    /**
     * Обработать повторную попытку с экспоненциальной задержкой
     */
    async handleRetry(action) {
        const newRetryCount = (action.retryCount || 0) + 1;

        if (newRetryCount >= action.maxRetries) {
            await this.removeAction(action.id);
            this.notifyError(action, 'Превышено количество попыток');

            // Отправляем событие для отслеживания неудачных действий
            const event = new CustomEvent('sm:sync-action-failed', {
                detail: { action }
            });
            window.dispatchEvent(event);
            return;
        }

        // Экспоненциальная задержка: 1s, 2s, 4s, 8s, 16s...
        const delayMs = Math.min(1000 * Math.pow(2, newRetryCount - 1), 60000);
        const nextRetryAt = Date.now() + delayMs;

        await this.updateAction(action.id, {
            retryCount: newRetryCount,
            nextRetryAt
        });

        // Планируем retry
        setTimeout(() => {
            if (navigator.onLine) {
                this.processQueue();
            }
        }, delayMs);
    }

    /**
     * Обновить бейдж с количеством ожидающих действий
     */
    async updatePendingBadge() {
        try {
            const count = await this.getPendingCount();

            if ('setAppBadge' in navigator) {
                if (count > 0) {
                    navigator.setAppBadge(count);
                } else {
                    navigator.clearAppBadge();
                }
            }

            const event = new CustomEvent('sm:sync-queue-updated', {
                detail: { count }
            });
            window.dispatchEvent(event);
        } catch (e) {
            console.warn('Badge update failed:', e);
        }
    }

    /**
     * Уведомить о добавлении в очередь
     */
    notifyQueued(action) {
        const description = action.description || action.type;
        if (window.toast) {
            window.toast.info('В очередь: ' + description);
        }
    }

    /**
     * Уведомить об успешной синхронизации
     */
    notifySuccess(action) {
        const description = action.description || action.type;
        if (window.toast) {
            window.toast.success('Синхронизировано: ' + description);
        }
    }

    /**
     * Уведомить об ошибке
     */
    notifyError(action, error) {
        const description = action.description || action.type;
        if (window.toast) {
            window.toast.error('Ошибка синхронизации: ' + description);
        }
        console.error('Sync error:', description, error);
    }

    /**
     * Уведомить о завершении синхронизации
     */
    notifySyncComplete(successCount, failCount) {
        if (failCount > 0) {
            if (window.toast) {
                window.toast.warning(
                    'Синхронизация: ' + successCount + ' успешно, ' + failCount + ' с ошибками'
                );
            }
        } else {
            if (window.toast) {
                window.toast.success('Синхронизировано: ' + successCount + ' действий');
            }
        }
    }

    /**
     * Проверить поддержку Background Sync
     */
    static isSupported() {
        return 'serviceWorker' in navigator && 'SyncManager' in window;
    }

    /**
     * Проверить поддержку Periodic Background Sync
     */
    static isPeriodicSyncSupported() {
        return 'serviceWorker' in navigator && 'PeriodicSyncManager' in window;
    }

    // ==========================================
    // Periodic Background Sync
    // ==========================================

    /**
     * Зарегистрировать периодическую синхронизацию
     * @param {string} tag - Идентификатор синхронизации
     * @param {number} minInterval - Минимальный интервал в миллисекундах
     */
    async registerPeriodicSync(tag, minInterval = 60 * 60 * 1000) {
        if (!SmBackgroundSync.isPeriodicSyncSupported()) {
            console.warn('Periodic Background Sync не поддерживается');
            // Fallback на setInterval
            this.startFallbackPeriodicSync(tag, minInterval);
            return false;
        }

        try {
            const registration = await navigator.serviceWorker.ready;
            const status = await navigator.permissions.query({
                name: 'periodic-background-sync'
            });

            if (status.state === 'granted') {
                await registration.periodicSync.register(tag, {
                    minInterval
                });
                await this.savePeriodicSyncConfig(tag, minInterval);
                return true;
            } else {
                console.warn('Периодическая синхронизация не разрешена');
                this.startFallbackPeriodicSync(tag, minInterval);
                return false;
            }
        } catch (e) {
            console.warn('Ошибка регистрации периодической синхронизации:', e);
            this.startFallbackPeriodicSync(tag, minInterval);
            return false;
        }
    }

    /**
     * Сохранить конфигурацию периодической синхронизации
     */
    async savePeriodicSyncConfig(tag, interval) {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.PERIODIC_STORE, 'readwrite');
            const store = tx.objectStore(this.PERIODIC_STORE);
            const request = store.put({
                id: tag,
                interval,
                lastSync: null,
                enabled: true
            });

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Fallback периодическая синхронизация через setInterval
     */
    startFallbackPeriodicSync(tag, interval) {
        // Очищаем предыдущий интервал если есть
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }

        // Запускаем периодическую проверку
        this.syncInterval = setInterval(async () => {
            if (navigator.onLine) {
                await this.handlePeriodicSync(tag);
            }
        }, Math.max(interval, 60000)); // Минимум 1 минута

        // Сразу выполняем первую синхронизацию
        if (navigator.onLine) {
            this.handlePeriodicSync(tag);
        }
    }

    /**
     * Обработка периодической синхронизации
     */
    async handlePeriodicSync(tag) {
        const handlers = {
            'sm-sync-dashboard': this.syncDashboard.bind(this),
            'sm-sync-products': this.syncProducts.bind(this),
            'sm-sync-orders': this.syncOrders.bind(this),
            'sm-sync-analytics': this.syncAnalytics.bind(this)
        };

        const handler = handlers[tag];
        if (handler) {
            try {
                await handler();
                await this.updateLastSyncTime(tag);
            } catch (e) {
                console.warn('Periodic sync error for', tag, e);
            }
        }
    }

    /**
     * Обновить время последней синхронизации
     */
    async updateLastSyncTime(tag) {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.PERIODIC_STORE, 'readwrite');
            const store = tx.objectStore(this.PERIODIC_STORE);
            const getRequest = store.get(tag);

            getRequest.onsuccess = () => {
                const config = getRequest.result || { id: tag };
                config.lastSync = Date.now();
                const putRequest = store.put(config);
                putRequest.onsuccess = () => resolve();
                putRequest.onerror = () => reject(putRequest.error);
            };

            getRequest.onerror = () => reject(getRequest.error);
        });
    }

    /**
     * Получить время последней синхронизации
     */
    async getLastSyncTime(tag) {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.PERIODIC_STORE, 'readonly');
            const store = tx.objectStore(this.PERIODIC_STORE);
            const request = store.get(tag);

            request.onsuccess = () => {
                resolve(request.result?.lastSync || null);
            };
            request.onerror = () => reject(request.error);
        });
    }

    // ==========================================
    // Синхронизация данных
    // ==========================================

    /**
     * Синхронизация дашборда
     */
    async syncDashboard() {
        if (!navigator.onLine) return;

        try {
            const response = await fetch('/api/dashboard/summary', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                if (window.SmCache) {
                    await window.SmCache.set('dashboard', 'summary', data);
                }

                // Отправляем событие обновления
                const event = new CustomEvent('sm:dashboard-synced', {
                    detail: { data }
                });
                window.dispatchEvent(event);
            }
        } catch (e) {
            console.warn('Dashboard sync failed:', e);
        }
    }

    /**
     * Синхронизация товаров
     */
    async syncProducts() {
        if (!navigator.onLine) return;

        try {
            const response = await fetch('/api/products?limit=100', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                if (window.SmCache) {
                    await window.SmCache.set('products', 'list', data);
                }

                const event = new CustomEvent('sm:products-synced', {
                    detail: { count: data.data?.length || 0 }
                });
                window.dispatchEvent(event);
            }
        } catch (e) {
            console.warn('Products sync failed:', e);
        }
    }

    /**
     * Синхронизация заказов
     */
    async syncOrders() {
        if (!navigator.onLine) return;

        try {
            const response = await fetch('/api/orders?limit=50', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                if (window.SmCache) {
                    await window.SmCache.set('orders', 'list', data);
                }

                const event = new CustomEvent('sm:orders-synced', {
                    detail: { count: data.data?.length || 0 }
                });
                window.dispatchEvent(event);
            }
        } catch (e) {
            console.warn('Orders sync failed:', e);
        }
    }

    /**
     * Синхронизация аналитики
     */
    async syncAnalytics() {
        if (!navigator.onLine) return;

        try {
            const response = await fetch('/api/analytics/summary', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                if (window.SmCache) {
                    await window.SmCache.set('analytics', 'summary', data);
                }

                const event = new CustomEvent('sm:analytics-synced', {
                    detail: { data }
                });
                window.dispatchEvent(event);
            }
        } catch (e) {
            console.warn('Analytics sync failed:', e);
        }
    }

    // ==========================================
    // Утилиты
    // ==========================================

    /**
     * Получить статистику очереди
     */
    async getQueueStats() {
        const actions = await this.getPendingActions();

        const stats = {
            total: actions.length,
            byPriority: {
                critical: 0,
                high: 0,
                normal: 0,
                low: 0
            },
            byType: {},
            oldestAction: null,
            failedRetries: 0
        };

        for (const action of actions) {
            // По приоритету
            if (action.priority === 1) stats.byPriority.critical++;
            else if (action.priority === 2) stats.byPriority.high++;
            else if (action.priority === 4) stats.byPriority.low++;
            else stats.byPriority.normal++;

            // По типу
            const type = action.type || 'unknown';
            stats.byType[type] = (stats.byType[type] || 0) + 1;

            // Самое старое действие
            if (!stats.oldestAction || action.timestamp < stats.oldestAction.timestamp) {
                stats.oldestAction = action;
            }

            // С ошибками
            if (action.retryCount > 0) stats.failedRetries++;
        }

        return stats;
    }

    /**
     * Экспорт очереди для отладки
     */
    async exportQueue() {
        const actions = await this.getPendingActions();
        return JSON.stringify(actions, null, 2);
    }

    /**
     * Остановить все периодические синхронизации
     */
    stopPeriodicSync() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
        }
    }

    /**
     * Уничтожить экземпляр (cleanup)
     */
    destroy() {
        this.stopPeriodicSync();
        if (this.db) {
            this.db.close();
            this.db = null;
        }
    }
}

window.SmBackgroundSync = new SmBackgroundSync();

// Автоматическая синхронизация при восстановлении соединения
window.addEventListener('online', async () => {
    // Небольшая задержка чтобы соединение стабилизировалось
    setTimeout(async () => {
        await window.SmBackgroundSync.processQueue();
    }, 1000);
});

// Визуальный индикатор при потере соединения
window.addEventListener('offline', () => {
    // Показываем индикатор что действия будут в очереди
    window.SmBackgroundSync.getPendingCount().then(count => {
        if (count > 0 && window.toast) {
            window.toast.warning('Нет соединения. ' + count + ' действий в очереди синхронизации.');
        }
    });
});

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', async () => {
    try {
        await window.SmBackgroundSync.init();
        await window.SmBackgroundSync.updatePendingBadge();

        if (navigator.onLine) {
            await window.SmBackgroundSync.processQueue();
        }

        // Регистрируем периодическую синхронизацию дашборда (каждый час)
        // Только если пользователь авторизован
        if (document.querySelector('meta[name="user-authenticated"]')) {
            await window.SmBackgroundSync.registerPeriodicSync(
                'sm-sync-dashboard',
                60 * 60 * 1000 // 1 час
            );
        }
    } catch (e) {
        console.warn('Background Sync init failed:', e);
    }
});

// Обработка сообщений от Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'BACKGROUND_SYNC') {
            window.SmBackgroundSync.processQueue();
        }

        if (event.data && event.data.type === 'PERIODIC_SYNC') {
            window.SmBackgroundSync.handlePeriodicSync(event.data.tag);
        }
    });
}

// Экспорт для ES модулей
export default SmBackgroundSync;
