<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $store->meta_title ?? $store->name }}</title>
    <meta name="description" content="{{ $store->meta_description ?? $store->description }}">
    @if($store->meta_keywords)
        <meta name="keywords" content="{{ $store->meta_keywords }}">
    @endif

    @if($store->favicon)
        <link rel="icon" href="{{ asset('storage/' . $store->favicon) }}" type="image/x-icon">
    @endif

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family={{ urlencode($store->theme->heading_font ?? 'Inter') }}:400,500,600,700|{{ urlencode($store->theme->body_font ?? 'Inter') }}:400,500,600,700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $store->theme->primary_color ?? '#007AFF' }}',
                        secondary: '{{ $store->theme->secondary_color ?? '#5856D6' }}',
                        accent: '{{ $store->theme->accent_color ?? '#FF9500' }}',
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --primary: {{ $store->theme->primary_color ?? '#007AFF' }};
            --secondary: {{ $store->theme->secondary_color ?? '#5856D6' }};
            --accent: {{ $store->theme->accent_color ?? '#FF9500' }};
            --bg: {{ $store->theme->background_color ?? '#FFFFFF' }};
            --text: {{ $store->theme->text_color ?? '#1C1C1E' }};
            --header-bg: {{ $store->theme->header_bg_color ?? '#FFFFFF' }};
            --header-text: {{ $store->theme->header_text_color ?? '#1C1C1E' }};
            --footer-bg: {{ $store->theme->footer_bg_color ?? '#1C1C1E' }};
            --footer-text: {{ $store->theme->footer_text_color ?? '#FFFFFF' }};
        }

        body {
            font-family: '{{ $store->theme->body_font ?? 'Inter' }}', sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: '{{ $store->theme->heading_font ?? 'Inter' }}', sans-serif;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
            transition: filter 0.2s;
        }
        .btn-primary:hover {
            filter: brightness(0.9);
        }

        .btn-secondary {
            background: var(--secondary);
            color: #fff;
            transition: filter 0.2s;
        }
        .btn-secondary:hover {
            filter: brightness(0.9);
        }

        .btn-accent {
            background: var(--accent);
            color: #fff;
            transition: filter 0.2s;
        }
        .btn-accent:hover {
            filter: brightness(0.9);
        }

        .text-theme-primary { color: var(--primary); }
        .bg-theme-primary { background: var(--primary); }
        .border-theme-primary { border-color: var(--primary); }

        .text-theme-secondary { color: var(--secondary); }
        .bg-theme-secondary { background: var(--secondary); }

        .text-theme-accent { color: var(--accent); }
        .bg-theme-accent { background: var(--accent); }

        [x-cloak] { display: none !important; }

        {{ $store->theme->custom_css ?? '' }}
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

    @include('storefront.components.header', ['store' => $store])

    <main class="flex-1">
        @yield('content')
    </main>

    @include('storefront.components.footer', ['store' => $store])

    {{-- Глобальное уведомление (toast) --}}
    <div
        x-data="toastNotification()"
        x-on:show-toast.window="show($event.detail)"
        x-cloak
        class="fixed bottom-6 right-6 z-50"
    >
        <div
            x-show="visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="rounded-xl shadow-2xl px-5 py-4 flex items-center gap-3 max-w-sm"
            :class="type === 'success' ? 'bg-green-600 text-white' : type === 'error' ? 'bg-red-600 text-white' : 'bg-gray-800 text-white'"
        >
            <template x-if="type === 'success'">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </template>
            <template x-if="type === 'error'">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </template>
            <span x-text="message" class="text-sm font-medium"></span>
        </div>
    </div>

    <script>
        function toastNotification() {
            return {
                visible: false,
                message: '',
                type: 'success',
                timeout: null,
                show(detail) {
                    this.message = detail.message || '';
                    this.type = detail.type || 'success';
                    this.visible = true;
                    clearTimeout(this.timeout);
                    this.timeout = setTimeout(() => { this.visible = false; }, 3000);
                }
            }
        }

        function formatPrice(value) {
            return new Intl.NumberFormat('ru-RU', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(value);
        }
    </script>
</body>
</html>
