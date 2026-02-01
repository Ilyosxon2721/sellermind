// PWA Service Worker Registration (vite-plugin-pwa)
import { registerSW } from 'virtual:pwa-register';

const updateSW = registerSW({
    immediate: true,
    onNeedRefresh() {
        // New version available - show update prompt
        const shouldUpdate = confirm(
            '🔄 Доступна новая версия SellerMind.\n\n' +
            'Рекомендуется обновить для получения новых функций и исправлений.\n\n' +
            'Обновить сейчас?'
        );

        if (shouldUpdate) {
            updateSW(true);
        }
    },
    onOfflineReady() {
        // Optional: Show toast notification
        showToast('success', 'Приложение готово к работе офлайн');
    },
    onRegistered(registration) {
        // Check for updates periodically (every hour)
        if (registration) {
            setInterval(() => {
                registration.update();
            }, 60 * 60 * 1000);
        }
    },
    onRegisterError(error) {
        console.warn('⚠️ PWA: Service Worker registration failed:', error);
    }
});

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

// Expose updateSW globally for manual updates
window.updatePWA = () => {
    updateSW(true);
};

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

