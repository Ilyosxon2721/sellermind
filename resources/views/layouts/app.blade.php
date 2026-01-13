<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SellerMind') }}</title>
    <meta name="description" content="Платформа управления продажами на маркетплейсах">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SellerMind">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/build/manifest.json">

    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="152x152" href="/images/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/images/icons/icon-192x192.png">

    <!-- Resource Hints for Performance -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.bunny.net">

    <!-- Fonts with optimized loading -->
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%232563eb' rx='20' width='100' height='100'/><path fill='white' d='M50 15L15 35v30l35 20 35-20V35L50 15zm0 10l25 14v22L50 75 25 61V39l25-14z'/></svg>">

    <!-- Critical CSS inline for LCP optimization -->
    <style>
        body {
            margin: 0;
            background: #f9fafb;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .min-h-screen { min-height: 100vh; }
        .bg-gray-50 { background-color: #f9fafb; }
        [x-cloak] { display: none !important; }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/css/pwa-native.css', 'resources/js/pwa-detector.js', 'resources/js/app.js'])

    <!-- Initialize Alpine store with server-side auth data or check localStorage -->
    <script>
        document.addEventListener('alpine:init', async () => {
            // Initialize auth store with server data
            const authStore = Alpine.store('auth');

            @auth
            // Set user data from Laravel session
            if (!authStore.user) {
                authStore.user = @json(auth()->user());
            }

            // Set a fake token to mark as authenticated (for session-based auth)
            if (!authStore.token) {
                authStore.token = 'session-auth';
            }

            // Load companies for authenticated users (only if not already loaded)
            if (authStore.isAuthenticated && !authStore.hasCompanies) {
                await authStore.loadCompanies();
            }
            @endauth

            // If no auth from session, check localStorage for API token
            // This handles API-based authentication
            if (!authStore.token) {
                const token = localStorage.getItem('_x_auth_token');
                if (!token) {
                    // Not authenticated, redirect to login
                    if (!window.location.pathname.includes('/login') && !window.location.pathname.includes('/register') && window.location.pathname !== '/') {
                        window.location.href = '/login';
                    }
                }
            }
        });
    </script>
</head>
<body class="bg-gray-50">
    <!-- Splash Screen (PWA only) -->
    <x-splash-screen />

    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        @yield('content')
    </div>

    <!-- Company Prompt Modal -->
    <x-company-prompt-modal />

    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Loading Overlay -->
    <x-loading-overlay />

    <!-- Offline Indicator -->
    <x-offline-indicator />

    <!-- Bottom Tab Navigation (PWA only, mobile/tablet) -->
    @auth
    <x-bottom-tab-nav />
    @endauth

    <!-- Global Action Sheet -->
    <x-global-action-sheet />

    <!-- PWA Auto-registration (handled by vite-plugin-pwa) -->
    @vite('resources/js/pwa.js')

    <!-- PWA Install Prompt -->
    <script>
        let deferredPrompt;
        let pwaInstallButton = null;

        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent default install prompt
            e.preventDefault();
            deferredPrompt = e;

            // Show custom install button
            showInstallPromotion();
        });

        function showInstallPromotion() {
            // Create install button if not exists
            if (!pwaInstallButton && deferredPrompt) {
                pwaInstallButton = document.createElement('button');
                pwaInstallButton.innerHTML = `
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Установить приложение
                `;
                pwaInstallButton.className = 'fixed bottom-4 right-4 z-50 px-4 py-3 bg-blue-600 text-white rounded-xl shadow-lg hover:bg-blue-700 transition-all flex items-center font-medium';
                pwaInstallButton.onclick = installPWA;
                document.body.appendChild(pwaInstallButton);

                // Auto-hide after 10 seconds
                setTimeout(() => {
                    if (pwaInstallButton) {
                        pwaInstallButton.style.opacity = '0';
                        setTimeout(() => pwaInstallButton?.remove(), 300);
                    }
                }, 10000);
            }
        }

        async function installPWA() {
            if (!deferredPrompt) return;

            // Show install prompt
            deferredPrompt.prompt();

            // Wait for user response
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`PWA install ${outcome}`);

            // Clear the prompt
            deferredPrompt = null;
            pwaInstallButton?.remove();
            pwaInstallButton = null;
        }

        // Track if app was installed
        window.addEventListener('appinstalled', () => {
            console.log('✅ PWA: App installed successfully');
            deferredPrompt = null;
            pwaInstallButton?.remove();
        });

        // Expose install function globally
        window.installPWA = installPWA;
    </script>
</body>
</html>
