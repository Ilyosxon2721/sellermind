@props([
    'title' => '',
    'backUrl' => null,
    'badge' => null,
    'actions' => null
])

<header class="pwa-only sm-header">
    {{-- Left --}}
    <div class="flex items-center">
        @if($backUrl)
            <a href="{{ $backUrl }}" class="sm-header-back" onclick="if(navigator.vibrate) navigator.vibrate(10)">
                &#8592;
            </a>
        @endif

        <span class="sm-header-title">{{ $title }}</span>

        @if($badge)
            <span class="sm-header-badge">{{ $badge }}</span>
        @endif
    </div>

    {{-- Right --}}
    <div class="flex items-center gap-2">
        {{ $actions ?? $slot }}
    </div>
</header>
