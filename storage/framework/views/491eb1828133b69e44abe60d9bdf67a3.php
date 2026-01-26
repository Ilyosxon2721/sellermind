<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e(config('app.name', 'SellerMind')); ?></title>
    <meta name="description" content="Платформа управления продажами на маркетплейсах">

    <!-- SEO & Social Media Meta Tags -->
    <meta property="og:title" content="SellerMind - Управление маркетплейсами">
    <meta property="og:description" content="Платформа управления продажами на маркетплейсах Wildberries, Ozon, Uzum">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo e(config('app.url')); ?>">
    <meta property="og:image" content="<?php echo e(asset('images/og-image.png')); ?>">
    
    <!--  Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SellerMind - Управление маркетплейсами">
    <meta name="twitter:description" content="Платформа управления продажами на маркетплейсах">
    <meta name="twitter:image" content="<?php echo e(asset('images/og-image.png')); ?>">
    
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
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/css/pwa-native.css', 'resources/js/pwa-detector.js', 'resources/js/app.js']); ?>

    <!-- Initialize Alpine store with server-side auth data or check localStorage -->
    <script>
        document.addEventListener('alpine:init', async () => {
            // Initialize auth store with server data
            const authStore = Alpine.store('auth');

            <?php if(auth()->guard()->check()): ?>
            // Set user data from Laravel session
            if (!authStore.user) {
                authStore.user = <?php echo json_encode(auth()->user(), 15, 512) ?>;
            }

            // Set a fake token to mark as authenticated (for session-based auth)
            if (!authStore.token) {
                authStore.token = 'session-auth';
            }

            // Load companies for authenticated users (only if not already loaded)
            if (authStore.isAuthenticated && !authStore.hasCompanies) {
                await authStore.loadCompanies();
            }
            <?php endif; ?>

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
    <?php if (isset($component)) { $__componentOriginal1cf7ddb08d3976da931ed0aee29f0761 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cf7ddb08d3976da931ed0aee29f0761 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.splash-screen','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('splash-screen'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cf7ddb08d3976da931ed0aee29f0761)): ?>
<?php $attributes = $__attributesOriginal1cf7ddb08d3976da931ed0aee29f0761; ?>
<?php unset($__attributesOriginal1cf7ddb08d3976da931ed0aee29f0761); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cf7ddb08d3976da931ed0aee29f0761)): ?>
<?php $component = $__componentOriginal1cf7ddb08d3976da931ed0aee29f0761; ?>
<?php unset($__componentOriginal1cf7ddb08d3976da931ed0aee29f0761); ?>
<?php endif; ?>

    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        <!-- Hamburger Menu & Sidebar Overlay (Mobile Only) -->
        <?php if (isset($component)) { $__componentOriginalac6d9f0b02c9ffcb58009e3f010ab3a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalac6d9f0b02c9ffcb58009e3f010ab3a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.hamburger-menu','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('hamburger-menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalac6d9f0b02c9ffcb58009e3f010ab3a2)): ?>
<?php $attributes = $__attributesOriginalac6d9f0b02c9ffcb58009e3f010ab3a2; ?>
<?php unset($__attributesOriginalac6d9f0b02c9ffcb58009e3f010ab3a2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalac6d9f0b02c9ffcb58009e3f010ab3a2)): ?>
<?php $component = $__componentOriginalac6d9f0b02c9ffcb58009e3f010ab3a2; ?>
<?php unset($__componentOriginalac6d9f0b02c9ffcb58009e3f010ab3a2); ?>
<?php endif; ?>
        
        <?php echo $__env->yieldContent('content'); ?>
    </div>

    <!-- Company Prompt Modal -->
    <?php if (isset($component)) { $__componentOriginala9befc8f15d434157c0a4e635f27f525 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala9befc8f15d434157c0a4e635f27f525 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.company-prompt-modal','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('company-prompt-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala9befc8f15d434157c0a4e635f27f525)): ?>
<?php $attributes = $__attributesOriginala9befc8f15d434157c0a4e635f27f525; ?>
<?php unset($__attributesOriginala9befc8f15d434157c0a4e635f27f525); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala9befc8f15d434157c0a4e635f27f525)): ?>
<?php $component = $__componentOriginala9befc8f15d434157c0a4e635f27f525; ?>
<?php unset($__componentOriginala9befc8f15d434157c0a4e635f27f525); ?>
<?php endif; ?>

    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Loading Overlay -->
    <?php if (isset($component)) { $__componentOriginal115e82920da0ed7c897ee494af74b9d8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal115e82920da0ed7c897ee494af74b9d8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.loading-overlay','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('loading-overlay'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal115e82920da0ed7c897ee494af74b9d8)): ?>
<?php $attributes = $__attributesOriginal115e82920da0ed7c897ee494af74b9d8; ?>
<?php unset($__attributesOriginal115e82920da0ed7c897ee494af74b9d8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal115e82920da0ed7c897ee494af74b9d8)): ?>
<?php $component = $__componentOriginal115e82920da0ed7c897ee494af74b9d8; ?>
<?php unset($__componentOriginal115e82920da0ed7c897ee494af74b9d8); ?>
<?php endif; ?>

    <!-- Offline Indicator -->
    <?php if (isset($component)) { $__componentOriginal30ed851a7370ef0c75347addc2809e2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal30ed851a7370ef0c75347addc2809e2c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.offline-indicator','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('offline-indicator'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal30ed851a7370ef0c75347addc2809e2c)): ?>
<?php $attributes = $__attributesOriginal30ed851a7370ef0c75347addc2809e2c; ?>
<?php unset($__attributesOriginal30ed851a7370ef0c75347addc2809e2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal30ed851a7370ef0c75347addc2809e2c)): ?>
<?php $component = $__componentOriginal30ed851a7370ef0c75347addc2809e2c; ?>
<?php unset($__componentOriginal30ed851a7370ef0c75347addc2809e2c); ?>
<?php endif; ?>

    <!-- Bottom Tab Navigation (PWA only, mobile/tablet) -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
    <?php if (isset($component)) { $__componentOriginal3f4d1cf26e73a41d25220b5e3dc4f678 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3f4d1cf26e73a41d25220b5e3dc4f678 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.bottom-tab-nav','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('bottom-tab-nav'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3f4d1cf26e73a41d25220b5e3dc4f678)): ?>
<?php $attributes = $__attributesOriginal3f4d1cf26e73a41d25220b5e3dc4f678; ?>
<?php unset($__attributesOriginal3f4d1cf26e73a41d25220b5e3dc4f678); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3f4d1cf26e73a41d25220b5e3dc4f678)): ?>
<?php $component = $__componentOriginal3f4d1cf26e73a41d25220b5e3dc4f678; ?>
<?php unset($__componentOriginal3f4d1cf26e73a41d25220b5e3dc4f678); ?>
<?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Global Action Sheet -->
    <?php if (isset($component)) { $__componentOriginal61d567b65c2f08ee3259659437274fd6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal61d567b65c2f08ee3259659437274fd6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.global-action-sheet','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('global-action-sheet'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal61d567b65c2f08ee3259659437274fd6)): ?>
<?php $attributes = $__attributesOriginal61d567b65c2f08ee3259659437274fd6; ?>
<?php unset($__attributesOriginal61d567b65c2f08ee3259659437274fd6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal61d567b65c2f08ee3259659437274fd6)): ?>
<?php $component = $__componentOriginal61d567b65c2f08ee3259659437274fd6; ?>
<?php unset($__componentOriginal61d567b65c2f08ee3259659437274fd6); ?>
<?php endif; ?>

    <!-- PWA Auto-registration (handled by vite-plugin-pwa) -->
    <?php echo app('Illuminate\Foundation\Vite')('resources/js/pwa.js'); ?>

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
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views/layouts/app.blade.php ENDPATH**/ ?>