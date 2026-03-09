/**
 * SellerMind PWA - Service Worker Background Sync Handler
 *
 * Этот файл должен быть импортирован в основной Service Worker
 * или использован как дополнительный скрипт.
 *
 * Обрабатывает:
 * - Background Sync события
 * - Periodic Background Sync события
 * - Отправку сообщений в основное приложение
 */

// Имена тегов синхронизации
const SYNC_TAGS = {
    BACKGROUND: 'sm-background-sync',
    DASHBOARD: 'sm-sync-dashboard',
    PRODUCTS: 'sm-sync-products',
    ORDERS: 'sm-sync-orders',
    ANALYTICS: 'sm-sync-analytics'
};

/**
 * Обработчик Background Sync
 */
self.addEventListener('sync', (event) => {
    if (event.tag === SYNC_TAGS.BACKGROUND) {
        event.waitUntil(handleBackgroundSync());
    }
});

/**
 * Обработчик Periodic Background Sync
 */
self.addEventListener('periodicsync', (event) => {
    const periodicTags = [
        SYNC_TAGS.DASHBOARD,
        SYNC_TAGS.PRODUCTS,
        SYNC_TAGS.ORDERS,
        SYNC_TAGS.ANALYTICS
    ];

    if (periodicTags.includes(event.tag)) {
        event.waitUntil(handlePeriodicSync(event.tag));
    }
});

/**
 * Выполнить Background Sync
 */
async function handleBackgroundSync() {
    // Отправляем сообщение в основное приложение
    const clients = await self.clients.matchAll({ type: 'window' });

    for (const client of clients) {
        client.postMessage({
            type: 'BACKGROUND_SYNC',
            timestamp: Date.now()
        });
    }

    // Если нет активных клиентов, обрабатываем в SW
    if (clients.length === 0) {
        await processQueueInServiceWorker();
    }
}

/**
 * Выполнить Periodic Sync
 */
async function handlePeriodicSync(tag) {
    const clients = await self.clients.matchAll({ type: 'window' });

    for (const client of clients) {
        client.postMessage({
            type: 'PERIODIC_SYNC',
            tag,
            timestamp: Date.now()
        });
    }

    // Если нет активных клиентов, можем показать уведомление
    if (clients.length === 0) {
        await handlePeriodicSyncInBackground(tag);
    }
}

/**
 * Обработка очереди внутри Service Worker
 * (когда приложение закрыто)
 */
async function processQueueInServiceWorker() {
    const DB_NAME = 'sm-sync-queue';
    const STORE_NAME = 'pending-actions';

    try {
        const db = await openDatabase(DB_NAME, STORE_NAME);
        const actions = await getAllFromStore(db, STORE_NAME);

        for (const action of actions) {
            try {
                const response = await fetch(action.url, {
                    method: action.method || 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: action.body ? JSON.stringify(action.body) : undefined,
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    await deleteFromStore(db, STORE_NAME, action.id);

                    // Показываем уведомление об успехе
                    await showNotification(
                        'Синхронизировано',
                        action.description || action.type
                    );
                }
            } catch (e) {
                console.warn('SW sync action failed:', e);
            }
        }

        db.close();
    } catch (e) {
        console.error('SW processQueue error:', e);
    }
}

/**
 * Обработка периодической синхронизации в фоне
 */
async function handlePeriodicSyncInBackground(tag) {
    const endpoints = {
        'sm-sync-dashboard': '/api/dashboard/summary',
        'sm-sync-products': '/api/products?limit=100',
        'sm-sync-orders': '/api/orders?limit=50',
        'sm-sync-analytics': '/api/analytics/summary'
    };

    const url = endpoints[tag];
    if (!url) return;

    try {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        if (response.ok) {
            const data = await response.json();

            // Кэшируем данные
            const cache = await caches.open('sm-periodic-sync-cache');
            await cache.put(url, new Response(JSON.stringify(data)));

            // Если есть новые важные данные, показываем уведомление
            if (tag === 'sm-sync-orders' && data.new_orders > 0) {
                await showNotification(
                    'Новые заказы',
                    'У вас ' + data.new_orders + ' новых заказов'
                );
            }
        }
    } catch (e) {
        console.warn('Periodic sync failed for', tag, e);
    }
}

/**
 * Показать уведомление
 */
async function showNotification(title, body) {
    if (self.registration.showNotification) {
        await self.registration.showNotification(title, {
            body,
            icon: '/images/icons/icon-192x192.png',
            badge: '/images/icons/icon-72x72.png',
            tag: 'sm-sync-notification',
            renotify: false
        });
    }
}

// ==========================================
// IndexedDB helpers для Service Worker
// ==========================================

function openDatabase(dbName, storeName) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(dbName, 2);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(storeName)) {
                const store = db.createObjectStore(storeName, {
                    keyPath: 'id',
                    autoIncrement: true
                });
                store.createIndex('type', 'type', { unique: false });
                store.createIndex('timestamp', 'timestamp', { unique: false });
                store.createIndex('priority', 'priority', { unique: false });
            }
        };
    });
}

function getAllFromStore(db, storeName) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(storeName, 'readonly');
        const store = tx.objectStore(storeName);
        const request = store.getAll();

        request.onsuccess = () => resolve(request.result || []);
        request.onerror = () => reject(request.error);
    });
}

function deleteFromStore(db, storeName, id) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);
        const request = store.delete(id);

        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

// Экспорт для использования в SW
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        SYNC_TAGS,
        handleBackgroundSync,
        handlePeriodicSync
    };
}
