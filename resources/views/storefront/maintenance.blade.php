<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $store->name ?? 'Магазин' }} - На обслуживании</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Inter:400,500,600,700&display=swap" rel="stylesheet">
    @if(isset($store) && $store->favicon)
        <link rel="icon" href="{{ asset('storage/' . $store->favicon) }}" type="image/x-icon">
    @endif
    <style>
        body { font-family: 'Inter', sans-serif; }
        :root {
            --primary: {{ isset($store) && $store->theme ? ($store->theme->primary_color ?? '#007AFF') : '#007AFF' }};
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
    <div class="max-w-md w-full text-center">
        {{-- Логотип или название --}}
        @if(isset($store) && $store->logo)
            <img
                src="{{ asset('storage/' . $store->logo) }}"
                alt="{{ $store->name }}"
                class="h-14 mx-auto mb-8 object-contain"
            >
        @elseif(isset($store))
            <h1 class="text-2xl font-bold text-gray-900 mb-8">{{ $store->name }}</h1>
        @endif

        {{-- Иконка --}}
        <div class="w-24 h-24 mx-auto mb-8 rounded-full bg-white shadow-sm flex items-center justify-center">
            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>

        {{-- Текст --}}
        <h2 class="text-xl font-semibold text-gray-900 mb-3">
            Магазин на обслуживании
        </h2>
        <p class="text-gray-500 leading-relaxed mb-8">
            Мы проводим плановые работы для улучшения качества обслуживания.
            Скоро мы вернемся!
        </p>

        {{-- Контакты --}}
        @if(isset($store) && ($store->phone || $store->email))
            <div class="bg-white rounded-2xl p-5 shadow-sm inline-flex flex-col gap-3">
                @if($store->phone)
                    <a href="tel:{{ $store->phone }}" class="flex items-center gap-2.5 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ $store->phone }}
                    </a>
                @endif
                @if($store->email)
                    <a href="mailto:{{ $store->email }}" class="flex items-center gap-2.5 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ $store->email }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</body>
</html>
