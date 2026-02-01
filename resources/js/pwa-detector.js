/**
 * PWA Mode Detector
 *
 * Detects if app is running in PWA installed mode (standalone)
 * vs regular browser mode, and applies appropriate classes/behavior
 */

// Run detection immediately before DOM is ready to prevent flash of wrong content
(function() {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
    const isIOSStandalone = window.navigator.standalone === true;
    if (isStandalone || isIOSStandalone) {
        document.documentElement.classList.add('pwa-mode');
    } else {
        document.documentElement.classList.add('browser-mode');
    }
})();

class PWADetector {
    constructor() {
        this.isPWA = false;
        this.isIOS = false;
        this.isAndroid = false;
        this.displayMode = 'browser';

        this.init();
    }

    init() {
        // Detect platform
        this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        this.isAndroid = /Android/.test(navigator.userAgent);

        // Detect PWA display mode
        this.detectDisplayMode();

        // Set global flag
        window.isPWA = this.isPWA;
        window.isPWAInstalled = this.isPWA;
        window.isIOS = this.isIOS;
        window.isAndroid = this.isAndroid;

        // Apply PWA class to document
        if (this.isPWA) {
            document.documentElement.classList.add('pwa-mode');

            // Platform-specific classes
            if (this.isIOS) {
                document.documentElement.classList.add('pwa-ios');
            } else if (this.isAndroid) {
                document.documentElement.classList.add('pwa-android');
            }

            // Set cookie for server-side detection
            document.cookie = 'pwa_installed=true; path=/; max-age=31536000'; // 1 year

        } else {
            document.documentElement.classList.add('browser-mode');
            document.cookie = 'pwa_installed=false; path=/; max-age=31536000';
        }

        // Listen for display mode changes (when user installs PWA)
        this.watchDisplayModeChanges();
    }

    detectDisplayMode() {
        // Method 1: Check display-mode media query
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches;

        // Method 2: Check iOS standalone flag
        const isIOSStandalone = window.navigator.standalone === true;

        // Method 3: Check if opened from Android home screen
        const isAndroidStandalone = document.referrer.includes('android-app://');

        this.isPWA = isStandalone || isIOSStandalone || isAndroidStandalone;

        if (this.isPWA) {
            this.displayMode = 'standalone';
        } else if (window.matchMedia('(display-mode: fullscreen)').matches) {
            this.displayMode = 'fullscreen';
        } else if (window.matchMedia('(display-mode: minimal-ui)').matches) {
            this.displayMode = 'minimal-ui';
        } else {
            this.displayMode = 'browser';
        }
    }

    watchDisplayModeChanges() {
        // Watch for display mode changes
        const standaloneQuery = window.matchMedia('(display-mode: standalone)');

        if (standaloneQuery.addEventListener) {
            standaloneQuery.addEventListener('change', (e) => {
                if (e.matches && !this.isPWA) {
                    // User just installed PWA - reload to apply PWA mode
                    window.location.reload();
                }
            });
        }
    }

    // Check if device is mobile (phone or tablet)
    get isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    // Get platform name
    get platform() {
        if (this.isIOS) return 'ios';
        if (this.isAndroid) return 'android';
        return 'web';
    }
}

// Initialize detector
const pwaDetector = new PWADetector();

// Export
export default pwaDetector;
window.pwaDetector = pwaDetector;
