<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SellerMind') }}</title>
    <meta name="description" content="Платформа управления продажами на маркетплейсах">

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
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @auth
    <!-- Initialize Alpine store with server-side auth data -->
    <script>
        document.addEventListener('alpine:init', () => {
            // Initialize auth store with server data
            const authStore = Alpine.store('auth');

            // Set user data from Laravel session
            @if(auth()->user())
            if (!authStore.user) {
                authStore.user = @json(auth()->user());
            }

            // Set a fake token to mark as authenticated (for session-based auth)
            if (!authStore.token) {
                authStore.token = 'session-auth';
            }

            // Load user's companies if not already loaded
            if (!authStore.currentCompany && authStore.companies.length === 0) {
                // This will be loaded by the dashboard itself
            }
            @endif
        });
    </script>
    @endauth
</head>
<body class="bg-gray-50">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        @yield('content')
    </div>

    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
</body>
</html>
