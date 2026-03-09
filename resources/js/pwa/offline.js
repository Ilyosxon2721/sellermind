/**
 * SellerMind PWA - Offline Support with Optimistic UI
 *
 * Интеграция с SmBackgroundSync для:
 * - Автоматической постановки запросов в очередь при offline
 * - Optimistic UI обновлений
 * - Кэширования данных для offline доступа
 */

class SmOffline {
    constructor() {
        this.isOnline = navigator.onLine;
        this.listeners = [];
        this.pendingUIUpdates = new Map();

        window.addEventListener('online', () => this.setOnline(true));
        window.addEventListener('offline', () => this.setOnline(false));

        // Слушаем события синхронизации
        window.addEventListener('sm:sync-queue-updated', (e) => {
            this.onQueueUpdated(e.detail.count);
        });

        window.addEventListener('sm:sync-action-failed', (e) => {
            this.onActionFailed(e.detail.action);
        });
    }

    setOnline(status) {
        const wasOffline = !this.isOnline;
        this.isOnline = status;
        this.listeners.forEach(fn => fn(status));

        if (status) {
            this.showToast('Соединение восстановлено', 'success');

            // Если были offline, показываем инфо о синхронизации
            if (wasOffline) {
                this.showSyncStatus();
            }
        } else {
            this.showToast('Нет соединения. Изменения будут сохранены локально.', 'warning');
        }
    }

    /**
     * Показать статус синхронизации
     */
    async showSyncStatus() {
        if (window.SmBackgroundSync) {
            const count = await window.SmBackgroundSync.getPendingCount();
            if (count > 0) {
                this.showToast('Синхронизация ' + count + ' отложенных действий...', 'info');
            }
        }
    }

    /**
     * Обработка обновления очереди
     */
    onQueueUpdated(count) {
        // Обновляем UI индикатор если есть
        const indicator = document.querySelector('[data-sync-count]');
        if (indicator) {
            indicator.textContent = count;
            indicator.classList.toggle('hidden', count === 0);
        }
    }

    /**
     * Обработка неудачного действия
     */
    onActionFailed(action) {
        // Откатываем optimistic UI если было
        const rollback = this.pendingUIUpdates.get(action.id);
        if (rollback) {
            rollback();
            this.pendingUIUpdates.delete(action.id);
        }

        this.showToast('Не удалось выполнить: ' + (action.description || action.type), 'error');
    }

    onStatusChange(callback) {
        this.listeners.push(callback);
        // Сразу вызываем с текущим статусом
        callback(this.isOnline);
    }

    removeStatusListener(callback) {
        this.listeners = this.listeners.filter(fn => fn !== callback);
    }

    showToast(message, type = 'info') {
        // Используем глобальный toast если есть
        if (window.toast) {
            window.toast[type] ? window.toast[type](message) : window.toast.info(message);
            return;
        }

        // Fallback на собственную реализацию
        const toast = document.createElement('div');
        toast.className = 'sm-toast sm-toast-' + type;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('visible'), 10);
        setTimeout(() => {
            toast.classList.remove('visible');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Fetch с автоматическим кэшированием и fallback на кэш
     */
    async fetch(url, options = {}) {
        const cacheKey = options.cacheKey || url;
        const storeName = options.storeName || 'dashboard';

        let cachedData = null;
        try {
            cachedData = await window.SmCache.get(storeName, cacheKey);
        } catch (e) {}

        if (!this.isOnline) {
            if (cachedData) {
                return { data: cachedData, fromCache: true };
            }
            throw new Error('Нет соединения и нет кэша');
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    ...options.headers
                }
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const data = await response.json();

            try {
                await window.SmCache.set(storeName, cacheKey, data);
            } catch (e) {}

            return { data, fromCache: false };
        } catch (error) {
            if (cachedData) {
                return { data: cachedData, fromCache: true };
            }
            throw error;
        }
    }

    /**
     * POST/PUT/DELETE запрос с Background Sync при отсутствии соединения
     * @param {string} url - URL для запроса
     * @param {Object} options - Опции запроса
     * @param {Object} options.body - Тело запроса
     * @param {string} options.method - HTTP метод (POST, PUT, PATCH, DELETE)
     * @param {string} options.type - Тип действия для отображения (price-update, stock-update)
     * @param {string} options.description - Описание действия для уведомления
     * @param {boolean} options.queueIfOffline - Добавить в очередь если offline (по умолчанию true)
     * @returns {Promise<Object>} - Результат запроса или информация о постановке в очередь
     */
    async mutate(url, options = {}) {
        const {
            body,
            method = 'POST',
            type = 'action',
            description = 'Действие',
            queueIfOffline = true,
            ...fetchOptions
        } = options;

        // Если offline и разрешено добавление в очередь
        if (!this.isOnline && queueIfOffline) {
            if (window.SmBackgroundSync) {
                const actionId = await window.SmBackgroundSync.queueAction({
                    type,
                    url,
                    method,
                    body,
                    description
                });
                return {
                    queued: true,
                    actionId,
                    message: 'Действие добавлено в очередь синхронизации'
                };
            }
            throw new Error('Нет соединения');
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        try {
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    ...fetchOptions.headers
                },
                body: body ? JSON.stringify(body) : undefined,
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const data = await response.json();
            return { data, queued: false };
        } catch (error) {
            // При ошибке сети добавляем в очередь
            if (queueIfOffline && window.SmBackgroundSync && this.isNetworkError(error)) {
                const actionId = await window.SmBackgroundSync.queueAction({
                    type,
                    url,
                    method,
                    body,
                    description
                });
                return {
                    queued: true,
                    actionId,
                    message: 'Действие добавлено в очередь из-за ошибки сети'
                };
            }
            throw error;
        }
    }

    /**
     * Проверить, является ли ошибка сетевой
     */
    isNetworkError(error) {
        return (
            error.name === 'TypeError' ||
            error.message === 'Failed to fetch' ||
            error.message === 'Network request failed' ||
            error.message.includes('NetworkError')
        );
    }

    /**
     * Получить количество ожидающих действий в очереди
     */
    async getPendingCount() {
        if (window.SmBackgroundSync) {
            return await window.SmBackgroundSync.getPendingCount();
        }
        return 0;
    }

    /**
     * Принудительно синхронизировать очередь
     */
    async syncNow() {
        if (window.SmBackgroundSync && this.isOnline) {
            await window.SmBackgroundSync.processQueue();
        }
    }
}

window.SmOffline = new SmOffline();
