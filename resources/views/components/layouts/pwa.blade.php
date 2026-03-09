{{--
    PWA Layout Component
    Wrapper component for PWA pages

    Usage:
    <x-layouts.pwa :title="'Page Title'" :page-title="'Header Title'">
        <x-slot name="topBar">...</x-slot>
        <x-slot name="skeleton">...</x-slot>
        Main content here...
    </x-layouts.pwa>
--}}

@props([
    'title' => null,
    'pageTitle' => null,
    'pageSubtitle' => null,
    'showBack' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="pwa-mode">
<head>
    <meta charset="utf-8">

    {{-- PWA Viewport with safe-area support --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'SellerMind') }}</title>
    <meta name="description" content="Платформа управления продажами на маркетплейсах">

    {{-- PWA Meta Tags --}}
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SellerMind">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    <meta name="msapplication-tap-highlight" content="no">

    {{-- Manifest --}}
    <link rel="manifest" href="/build/manifest.json">

    {{-- Apple Touch Icons --}}
    <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/icon-192x192.png">

    {{-- Favicon --}}
    <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/icon-72x72.png">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    {{-- Critical CSS for App Shell --}}
    <style>
        :root {
            --safe-area-inset-top: env(safe-area-inset-top, 0px);
            --safe-area-inset-bottom: env(safe-area-inset-bottom, 0px);
            --top-bar-height: 48px;
            --tab-bar-height: 56px;
            --top-bar-total: calc(var(--top-bar-height) + var(--safe-area-inset-top));
            --tab-bar-total: calc(var(--tab-bar-height) + var(--safe-area-inset-bottom));
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; -webkit-tap-highlight-color: transparent; }
        body {
            margin: 0;
            padding: 0;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            overscroll-behavior: none;
            overflow-x: hidden;
            -webkit-user-select: none;
            user-select: none;
        }
        input, textarea, [contenteditable="true"] {
            -webkit-user-select: text;
            user-select: text;
        }
        [x-cloak] { display: none !important; }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .skeleton {
            background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 0.375rem;
        }
        .dark .skeleton {
            background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
            background-size: 200% 100%;
        }
    </style>

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/css/pwa-native.css', 'resources/js/pwa-detector.js', 'resources/js/app.js', 'resources/js/pwa/auth.js', 'resources/js/pwa/haptic.js', 'resources/js/pwa/cache.js', 'resources/js/pwa/offline.js', 'resources/js/pwa/background-sync.js', 'resources/js/pwa/badge.js', 'resources/js/pwa/push.js'])

    {{-- PWA Detection --}}
    <script>
        (function() {
            var isStandalone = window.matchMedia('(display-mode: standalone)').matches;
            var isIOSStandalone = window.navigator.standalone === true;
            window.isPWAInstalled = isStandalone || isIOSStandalone;
            document.documentElement.classList.add(window.isPWAInstalled ? 'pwa-mode' : 'browser-mode');
            var userAgent = navigator.userAgent.toLowerCase();
            if (/iphone|ipad|ipod/.test(userAgent)) document.documentElement.classList.add('ios');
            else if (/android/.test(userAgent)) document.documentElement.classList.add('android');
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) document.documentElement.classList.add('dark');
        })();
    </script>

    {{-- Initialize Alpine Stores --}}
    <script>
        document.addEventListener('alpine:init', async () => {
            const authStore = Alpine.store('auth');
            @auth
            if (!authStore.user) authStore.user = @json(auth()->user());
            if (!authStore.token) authStore.token = 'session-auth';
            if (authStore.isAuthenticated && !authStore.hasCompanies) await authStore.loadCompanies();
            @endauth
            if (!authStore.token) {
                const token = localStorage.getItem('_x_auth_token');
                if (!token) {
                    const publicRoutes = ['/login', '/register', '/'];
                    if (!publicRoutes.some(route => window.location.pathname.includes(route))) {
                        window.location.href = '/login';
                    }
                }
            }
        });
    </script>
</head>
<body
    class="bg-gray-50 dark:bg-gray-900 antialiased"
    x-data="{
        showSkeleton: true,
        pageLoaded: false,
        init() {
            this.$nextTick(() => {
                setTimeout(() => {
                    this.showSkeleton = false;
                    this.pageLoaded = true;
                }, 100);
            });
        }
    }"
>
    {{-- Splash Screen --}}
    <x-splash-screen />

    {{-- PIN Screen --}}
    @auth
    <x-pin-screen />
    @endauth

    {{-- App Shell Container --}}
    <div class="min-h-screen flex flex-col">

        {{-- Status Bar Area --}}
        <div class="bg-blue-600 dark:bg-gray-800" style="height: var(--safe-area-inset-top);"></div>

        {{-- Top Navigation Bar --}}
        @if(isset($topBar))
            {{ $topBar }}
        @else
            <header
                class="sticky top-0 z-40 flex items-center justify-between px-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
                style="height: var(--top-bar-height); padding-top: var(--safe-area-inset-top);"
            >
                <div class="flex items-center min-w-[48px]">
                    @if($showBack)
                        <button
                            type="button"
                            onclick="history.back(); if(window.SmHaptic) window.SmHaptic.light();"
                            class="p-2 -ml-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                    @endif
                </div>
                <div class="flex-1 text-center">
                    <h1 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                        {{ $pageTitle ?? config('app.name', 'SellerMind') }}
                    </h1>
                    @if($pageSubtitle)
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $pageSubtitle }}</p>
                    @endif
                </div>
                <div class="flex items-center min-w-[48px] justify-end">
                    @if(isset($topBarActions))
                        {{ $topBarActions }}
                    @endif
                </div>
            </header>
        @endif

        {{-- Main Content Area --}}
        <main class="flex-1 overflow-y-auto overscroll-contain" style="padding-bottom: var(--tab-bar-total);">
            {{-- Skeleton Loading State --}}
            <div
                x-show="showSkeleton"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="p-4 space-y-4"
            >
                @if(isset($skeleton))
                    {{ $skeleton }}
                @else
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            @for($i = 0; $i < 4; $i++)
                                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                                    <div class="skeleton h-4 w-16 mb-2"></div>
                                    <div class="skeleton h-6 w-24"></div>
                                </div>
                            @endfor
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                            @for($i = 0; $i < 5; $i++)
                                <div class="flex items-center p-4 {{ $i < 4 ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                                    <div class="skeleton w-10 h-10 rounded-lg mr-3"></div>
                                    <div class="flex-1">
                                        <div class="skeleton h-4 w-3/4 mb-2"></div>
                                        <div class="skeleton h-3 w-1/2"></div>
                                    </div>
                                    <div class="skeleton h-4 w-12"></div>
                                </div>
                            @endfor
                        </div>
                    </div>
                @endif
            </div>

            {{-- Actual Content --}}
            <div
                x-show="!showSkeleton"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                {{ $slot }}
            </div>
        </main>

        {{-- Bottom Tab Bar --}}
        <x-pwa.tab-bar />
    </div>

    {{-- Company Prompt Modal --}}
    <x-company-prompt-modal />

    {{-- Toast Notifications Container --}}
    <div
        id="toast-container"
        class="fixed z-50 space-y-2"
        style="top: calc(var(--top-bar-height) + var(--safe-area-inset-top) + 1rem); right: 1rem; left: 1rem;"
    ></div>

    {{-- Loading Overlay --}}
    <x-loading-overlay />

    {{-- Offline Indicator --}}
    <x-offline-indicator />

    {{-- Global Action Sheet --}}
    <x-global-action-sheet />

    {{-- PWA More Menu --}}
    @auth
    <x-pwa.more-menu />
    @endauth

    {{-- PWA Auto-registration --}}
    @vite('resources/js/pwa.js')

    {{-- Additional Scripts --}}
    @stack('scripts')
</body>
</html>
