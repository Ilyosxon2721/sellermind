/**
 * SellerMind PWA - Service Worker
 *
 * Стратегии кэширования:
 * - App Shell: Cache First
 * - Static Assets: Cache First
 * - API: Network First (fallback to cache)
 * - Images: Stale While Revalidate
 */

const CACHE_VERSION = 'v5';

const CACHE_NAMES = {
    shell: `shell-${CACHE_VERSION}`,
    api: `api-${CACHE_VERSION}`,
    assets: `assets-${CACHE_VERSION}`,
    images: `images-${CACHE_VERSION}`
};

// App Shell URLs - Cache First
const SHELL_URLS = [
    '/',
    '/dashboard-flutter',
    '/marketplace/products',
    '/marketplace/orders',
    '/chat-pwa',
    '/analytics/pwa',
    '/products-pwa',
    '/profile-pwa',
    '/offline',
    '/offline.html'  // Static fallback
];

// Static Assets patterns - Cache First
const ASSET_PATTERNS = [
    /\.js$/,
    /\.css$/,
    /\.woff2?$/,
    /\.png$/,
    /\.svg$/,
    /\.ico$/
];

// API patterns - Network First
const API_PATTERNS = [
    /\/api\//
];

// Image patterns - Stale While Revalidate
const IMAGE_PATTERNS = [
    /\/storage\//,
    /cdn\.sellermind\.uz/,
    /\/images\//
];

// Maximum cache sizes
const MAX_CACHE_SIZE = {
    images: 100,
    api: 50,
    assets: 100
};

// ==========================================
// Install Event
// ==========================================

self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker v' + CACHE_VERSION);

    event.waitUntil(
        caches.open(CACHE_NAMES.shell)
            .then((cache) => {
                console.log('[SW] Precaching App Shell');
                // Кэшируем shell URLs, игнорируя ошибки для недоступных страниц
                return Promise.allSettled(
                    SHELL_URLS.map(url =>
                        cache.add(url).catch(err => {
                            console.warn('[SW] Failed to cache:', url, err.message);
                        })
                    )
                );
            })
            .then(() => {
                // Skip waiting - активировать сразу
                return self.skipWaiting();
            })
    );
});

// ==========================================
// Activate Event
// ==========================================

self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker v' + CACHE_VERSION);

    event.waitUntil(
        Promise.all([
            // Удаляем старые кэши
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => {
                            // Удаляем кэши со старой версией
                            return !Object.values(CACHE_NAMES).includes(cacheName);
                        })
                        .map((cacheName) => {
                            console.log('[SW] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        })
                );
            }),
            // Claim all clients
            self.clients.claim()
        ])
    );
});

// ==========================================
// Fetch Event
// ==========================================

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Пропускаем non-GET запросы (они будут обработаны Background Sync)
    if (request.method !== 'GET') {
        return;
    }

    // Пропускаем chrome-extension и другие не http(s) запросы
    if (!request.url.startsWith('http')) {
        return;
    }

    // Определяем стратегию кэширования
    if (isApiRequest(url)) {
        event.respondWith(networkFirstStrategy(request, CACHE_NAMES.api));
    } else if (isImageRequest(url, request)) {
        event.respondWith(staleWhileRevalidateStrategy(request, CACHE_NAMES.images));
    } else if (isStaticAsset(url)) {
        event.respondWith(cacheFirstStrategy(request, CACHE_NAMES.assets));
    } else if (isNavigationRequest(request)) {
        event.respondWith(networkFirstWithOfflineFallback(request));
    } else {
        // Для остальных - network first
        event.respondWith(networkFirstStrategy(request, CACHE_NAMES.assets));
    }
});

// ==========================================
// Cache Strategies
// ==========================================

/**
 * Cache First Strategy
 * Сначала проверяем кэш, если нет - идем в сеть
 */
async function cacheFirstStrategy(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            // Клонируем response перед кэшированием
            cache.put(request, networkResponse.clone());
            await limitCacheSize(cacheName, MAX_CACHE_SIZE.assets);
        }

        return networkResponse;
    } catch (error) {
        console.warn('[SW] Cache first fetch failed:', error);
        return new Response('Offline', { status: 503 });
    }
}

/**
 * Network First Strategy
 * Сначала идем в сеть, если ошибка - возвращаем кэш
 */
async function networkFirstStrategy(request, cacheName) {
    const cache = await caches.open(cacheName);

    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
            await limitCacheSize(cacheName, MAX_CACHE_SIZE.api);
        }

        return networkResponse;
    } catch (error) {
        const cachedResponse = await cache.match(request);

        if (cachedResponse) {
            console.log('[SW] Serving from cache:', request.url);
            return cachedResponse;
        }

        // Возвращаем пустой JSON для API запросов
        return new Response(JSON.stringify({
            error: 'offline',
            message: 'No connection and no cached data',
            cached: false
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

/**
 * Stale While Revalidate Strategy
 * Возвращаем кэш сразу, но обновляем его в фоне
 */
async function staleWhileRevalidateStrategy(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);

    // Fetch в фоне для обновления кэша
    const fetchPromise = fetch(request)
        .then((networkResponse) => {
            if (networkResponse.ok) {
                cache.put(request, networkResponse.clone());
                limitCacheSize(cacheName, MAX_CACHE_SIZE.images);
            }
            return networkResponse;
        })
        .catch(() => null);

    // Возвращаем кэш если есть, иначе ждем сеть
    if (cachedResponse) {
        return cachedResponse;
    }

    const networkResponse = await fetchPromise;
    if (networkResponse) {
        return networkResponse;
    }

    // Placeholder для изображений
    return new Response('', { status: 404 });
}

/**
 * Network First with Offline Fallback
 * Для навигации - показываем offline страницу при ошибке
 */
async function networkFirstWithOfflineFallback(request) {
    try {
        const networkResponse = await fetch(request);

        // Кэшируем успешные navigation запросы
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAMES.shell);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        // Пытаемся найти в кэше
        const cache = await caches.open(CACHE_NAMES.shell);
        const cachedResponse = await cache.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        // Fallback на offline страницу (сначала динамическую, потом статическую)
        let offlineResponse = await cache.match('/offline');
        if (offlineResponse) {
            return offlineResponse;
        }

        // Пробуем статический HTML файл
        offlineResponse = await cache.match('/offline.html');
        if (offlineResponse) {
            return offlineResponse;
        }

        // Последний fallback - встроенный HTML
        return new Response(getOfflineHTML(), {
            status: 503,
            headers: { 'Content-Type': 'text/html; charset=utf-8' }
        });
    }
}

// ==========================================
// Helper Functions
// ==========================================

function isApiRequest(url) {
    return API_PATTERNS.some(pattern => pattern.test(url.pathname));
}

function isImageRequest(url, request) {
    const isImagePattern = IMAGE_PATTERNS.some(pattern => pattern.test(url.href));
    const isImageType = request.destination === 'image';
    return isImagePattern || isImageType;
}

function isStaticAsset(url) {
    return ASSET_PATTERNS.some(pattern => pattern.test(url.pathname));
}

function isNavigationRequest(request) {
    return request.mode === 'navigate';
}

/**
 * Ограничить размер кэша
 */
async function limitCacheSize(cacheName, maxItems) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();

    if (keys.length > maxItems) {
        // Удаляем старые записи (FIFO)
        const deleteCount = keys.length - maxItems;
        for (let i = 0; i < deleteCount; i++) {
            await cache.delete(keys[i]);
        }
    }
}

/**
 * HTML для offline страницы (fallback)
 */
function getOfflineHTML() {
    return `<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - SellerMind</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 24px;
            padding: 48px;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .icon {
            width: 80px;
            height: 80px;
            background: #fef3c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .icon svg {
            width: 40px;
            height: 40px;
            color: #f59e0b;
        }
        h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 12px;
        }
        p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .status {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3"></path>
            </svg>
        </div>
        <h1>Нет соединения</h1>
        <p>Проверьте подключение к интернету и попробуйте снова.</p>
        <a href="javascript:location.reload()" class="btn">Повторить</a>
        <div class="status">
            SellerMind работает в офлайн режиме
        </div>
    </div>
</body>
</html>`;
}

// ==========================================
// Background Sync
// ==========================================

const SYNC_TAGS = {
    BACKGROUND: 'sm-background-sync',
    DASHBOARD: 'sm-sync-dashboard',
    PRODUCTS: 'sm-sync-products',
    ORDERS: 'sm-sync-orders',
    ANALYTICS: 'sm-sync-analytics'
};

self.addEventListener('sync', (event) => {
    console.log('[SW] Background sync:', event.tag);

    if (event.tag === SYNC_TAGS.BACKGROUND || event.tag === 'sync-actions') {
        event.waitUntil(syncPendingActions());
    }
});

self.addEventListener('periodicsync', (event) => {
    console.log('[SW] Periodic sync:', event.tag);

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
 * Синхронизация отложенных действий
 */
async function syncPendingActions() {
    const DB_NAME = 'sm-sync-queue';
    const STORE_NAME = 'pending-actions';

    try {
        const db = await openDatabase(DB_NAME, STORE_NAME);
        const actions = await getAllFromStore(db, STORE_NAME);

        console.log('[SW] Processing', actions.length, 'pending actions');

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
                    console.log('[SW] Synced action:', action.type);

                    // Уведомляем клиентов
                    await notifyClients({
                        type: 'SYNC_SUCCESS',
                        action: action.type,
                        description: action.description
                    });
                }
            } catch (error) {
                console.warn('[SW] Sync action failed:', error);
            }
        }

        db.close();
    } catch (error) {
        console.error('[SW] syncPendingActions error:', error);
    }
}

/**
 * Периодическая синхронизация
 */
async function handlePeriodicSync(tag) {
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
            const cache = await caches.open(CACHE_NAMES.api);
            await cache.put(url, new Response(JSON.stringify(data)));

            console.log('[SW] Periodic sync completed:', tag);

            // Уведомление о новых заказах
            if (tag === 'sm-sync-orders' && data.new_orders > 0) {
                await self.registration.showNotification('Новые заказы', {
                    body: 'У вас ' + data.new_orders + ' новых заказов',
                    icon: '/images/icons/icon-192x192.png',
                    badge: '/images/icons/badge-72x72.png',
                    tag: 'new-orders',
                    data: { url: '/marketplace/orders' }
                });
            }
        }
    } catch (error) {
        console.warn('[SW] Periodic sync failed:', tag, error);
    }
}

// ==========================================
// Push Notifications
// ==========================================

self.addEventListener('push', (event) => {
    console.log('[SW] Push received');

    let data = {
        title: 'SellerMind',
        body: 'Новое уведомление',
        icon: '/images/icons/icon-192x192.png',
        badge: '/images/icons/badge-72x72.png',
        url: '/dashboard-flutter'
    };

    if (event.data) {
        try {
            data = { ...data, ...event.data.json() };
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/images/icons/icon-192x192.png',
        badge: data.badge || '/images/icons/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/dashboard-flutter',
            dateOfArrival: Date.now()
        },
        actions: data.actions || [
            { action: 'open', title: 'Открыть' },
            { action: 'close', title: 'Закрыть' }
        ],
        tag: data.tag || 'default',
        renotify: data.renotify || false,
        requireInteraction: data.requireInteraction || false
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notification clicked:', event.action);

    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    const urlToOpen = event.notification.data?.url || '/dashboard-flutter';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                // Ищем уже открытое окно
                for (const client of windowClients) {
                    if (client.url.includes(urlToOpen) && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Открываем новое окно
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

self.addEventListener('notificationclose', (event) => {
    console.log('[SW] Notification closed');
});

// ==========================================
// Message Handler
// ==========================================

self.addEventListener('message', (event) => {
    console.log('[SW] Message received:', event.data);

    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({ version: CACHE_VERSION });
    }

    if (event.data.type === 'CLEAR_CACHE') {
        event.waitUntil(
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => caches.delete(cacheName))
                );
            }).then(() => {
                event.ports[0].postMessage({ success: true });
            })
        );
    }
});

// ==========================================
// IndexedDB Helpers
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

/**
 * Уведомить всех клиентов
 */
async function notifyClients(message) {
    const allClients = await clients.matchAll({ type: 'window' });

    for (const client of allClients) {
        client.postMessage(message);
    }
}

console.log('[SW] Service Worker loaded v' + CACHE_VERSION);
