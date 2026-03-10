<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SellerMind') }}</title>
    <meta name="description" content="Платформа управления продажами на маркетплейсах">

    <!-- SEO & Social Media Meta Tags -->
    <meta property="og:title" content="SellerMind - Управление маркетплейсами">
    <meta property="og:description" content="Платформа управления продажами на маркетплейсах Wildberries, Ozon, Uzum">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    
    <!--  Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SellerMind - Управление маркетплейсами">
    <meta name="twitter:description" content="Платформа управления продажами на маркетплейсах">
    <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">
    
    <!-- Robots (noindex for internal app pages) -->
    <meta name="robots" content="noindex, nofollow">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SellerMind">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/build/manifest.json">

    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/images/icons/icon-192x192.png">

    <!-- iOS Splash Screens -->
    <!-- iPhone SE, iPod touch -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-640x1136.png" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)">
    <!-- iPhone 8, 7, 6s, 6 -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-750x1334.png" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)">
    <!-- iPhone 8 Plus, 7 Plus, 6s Plus, 6 Plus -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-1242x2208.png" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3)">
    <!-- iPhone X, XS, 11 Pro, 12 mini, 13 mini -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-1125x2436.png" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)">
    <!-- iPhone XR, 11 -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-828x1792.png" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)">
    <!-- iPhone XS Max, 11 Pro Max -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-1242x2688.png" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3)">
    <!-- iPhone 12, 12 Pro, 13, 13 Pro, 14 -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-1170x2532.png" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3)">
    <!-- iPhone 12 Pro Max, 13 Pro Max, 14 Plus -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-1284x2778.png" media="(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3)">
    <!-- iPhone 14 Pro -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-1179x2556.png" media="(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3)">
    <!-- iPhone 14 Pro Max, 15 Plus, 15 Pro Max -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-1290x2796.png" media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3)">
    <!-- iPad Mini, Air -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-1536x2048.png" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2)">
    <!-- iPad Pro 10.5" -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-1668x2224.png" media="(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2)">
    <!-- iPad Pro 11" -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-1668x2388.png" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2)">
    <!-- iPad Pro 12.9" -->
    <link rel="apple-touch-startup-image" href="/images/splash/apple-splash-2048x2732.png" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2)">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/icon-72x72.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/icons/icon-72x72.png">

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
        /* PWA/Browser mode visibility - critical for preventing flash */
        .pwa-only { display: none !important; }
        .pwa-mode .pwa-only { display: block !important; }
        .pwa-mode .browser-only { display: none !important; }
        .browser-mode .browser-only { display: flex !important; }
    </style>

    <!-- Early PWA detection to prevent flash of wrong content -->
    <script>
        (function() {
            var isStandalone = window.matchMedia('(display-mode: standalone)').matches;
            var isIOSStandalone = window.navigator.standalone === true;
            if (isStandalone || isIOSStandalone) {
                document.documentElement.classList.add('pwa-mode');
            } else {
                document.documentElement.classList.add('browser-mode');
            }
            window.isPWAInstalled = isStandalone || isIOSStandalone;
        })();
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/css/pwa-native.css', 'resources/js/pwa-detector.js', 'resources/js/app.js', 'resources/js/pwa/auth.js', 'resources/js/pwa/haptic.js', 'resources/js/pwa/cache.js', 'resources/js/pwa/offline.js', 'resources/js/pwa/background-sync.js', 'resources/js/pwa/badge.js', 'resources/js/pwa/push.js'])

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

    <!-- PIN Screen (PWA only, shows if PIN is set) -->
    @auth
    <x-pin-screen />
    @endauth

    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        <!-- Hamburger Menu & Sidebar Overlay (Mobile Only) -->
        <x-hamburger-menu />
        
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

    <!-- PWA More Menu (triggered from tabbar) -->
    @auth
    <x-pwa.more-menu />
    @endauth

    <!-- PWA Tab Bar (standalone PWA mode only) -->
    @auth
    <x-pwa-tab-bar />
    @endauth

    <!-- PWA Auto-registration (handled by vite-plugin-pwa) -->
    @vite('resources/js/pwa.js')

    <!-- PWA Install Banner -->
    <x-pwa-install-banner />

    <!-- PWA Update Banner -->
    <x-pwa-update-banner />
    {{-- Chart.js для страниц с графиками --}}
    @stack('scripts')
</body>
</html>
