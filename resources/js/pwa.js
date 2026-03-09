// PWA Service Worker Registration
// Custom SW with advanced caching strategies

let swRegistration = null;

async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        console.warn('PWA: Service Worker not supported');
        return;
    }

    try {
        // Register custom service worker
        const registration = await navigator.serviceWorker.register('/sw.js', {
            scope: '/',
            updateViaCache: 'none'
        });

        swRegistration = registration;
        console.log('PWA: Service Worker registered', registration.scope);

        // Check for updates on registration
        registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            console.log('PWA: New Service Worker installing');

            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    // New version available
                    showUpdatePrompt();
                }
            });
        });

        // Check for updates periodically (every hour)
        setInterval(() => {
            registration.update();
        }, 60 * 60 * 1000);

        // Handle controller change (new SW activated)
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            console.log('PWA: Controller changed, reloading...');
        });

        // Listen for messages from SW
        navigator.serviceWorker.addEventListener('message', (event) => {
            console.log('PWA: Message from SW:', event.data);

            if (event.data.type === 'SYNC_SUCCESS') {
                showToast('success', 'Синхронизировано: ' + (event.data.description || event.data.action));
            }
        });

        // Initial offline ready notification
        if (registration.active) {
            showToast('success', 'Приложение готово к работе офлайн');
        }

    } catch (error) {
        console.error('PWA: Service Worker registration failed:', error);
    }
}

function showUpdatePrompt() {
    const shouldUpdate = confirm(
        'Доступна новая версия SellerMind.\n\n' +
        'Рекомендуется обновить для получения новых функций и исправлений.\n\n' +
        'Обновить сейчас?'
    );

    if (shouldUpdate) {
        updateServiceWorker();
    }
}

async function updateServiceWorker() {
    if (!swRegistration || !swRegistration.waiting) {
        window.location.reload();
        return;
    }

    // Tell waiting SW to skip waiting
    swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });

    // Wait a bit and reload
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

// Register on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', registerServiceWorker);
} else {
    registerServiceWorker();
}

// Helper function to show toast notifications
function showToast(type, message) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `flex items-center gap-3 px-6 py-4 rounded-xl shadow-lg transform transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' : 'bg-blue-500'
    } text-white`;

    toast.innerHTML = `
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            ${type === 'success'
                ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'
                : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
            }
        </svg>
        <span class="font-medium">${message}</span>
    `;

    container.appendChild(toast);

    // Auto-remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(1rem)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Expose updatePWA globally for manual updates
window.updatePWA = updateServiceWorker;

// Get current SW version
window.getPWAVersion = async () => {
    if (!swRegistration || !swRegistration.active) {
        return null;
    }

    return new Promise((resolve) => {
        const channel = new MessageChannel();
        channel.port1.onmessage = (event) => {
            resolve(event.data.version);
        };
        swRegistration.active.postMessage({ type: 'GET_VERSION' }, [channel.port2]);

        // Timeout fallback
        setTimeout(() => resolve(null), 1000);
    });
};

// Clear all SW caches
window.clearPWACache = async () => {
    if (!swRegistration || !swRegistration.active) {
        return false;
    }

    return new Promise((resolve) => {
        const channel = new MessageChannel();
        channel.port1.onmessage = (event) => {
            resolve(event.data.success);
        };
        swRegistration.active.postMessage({ type: 'CLEAR_CACHE' }, [channel.port2]);

        // Timeout fallback
        setTimeout(() => resolve(false), 3000);
    });
};

// Export registration for other modules
window.getSWRegistration = () => swRegistration;

// Detect and mark PWA standalone mode
function detectPWAMode() {
    // Check if app is running in standalone mode (installed PWA)
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                        window.navigator.standalone === true || // iOS
                        document.referrer.includes('android-app://'); // Android

    if (isStandalone) {
        // Set cookie to indicate PWA mode for server-side detection
        document.cookie = 'pwa_installed=true; path=/; max-age=31536000; SameSite=Lax';
    } else {
        // Set cookie to indicate browser mode
        document.cookie = 'pwa_installed=false; path=/; max-age=31536000; SameSite=Lax';
    }

    // Expose as global
    window.isPWAInstalled = isStandalone;

    return isStandalone;
}

// Run detection on load
const isPWA = detectPWAMode();

// Listen for display mode changes (when user installs/uninstalls)
window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
    if (e.matches) {
        document.cookie = 'pwa_installed=true; path=/; max-age=31536000; SameSite=Lax';
        window.isPWAInstalled = true;

        // Reload to apply new routing
        if (window.location.pathname === '/' || window.location.pathname === '') {
            window.location.reload();
        }
    } else {
        document.cookie = 'pwa_installed=false; path=/; max-age=31536000; SameSite=Lax';
        window.isPWAInstalled = false;
    }
});

