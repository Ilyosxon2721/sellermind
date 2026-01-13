{{-- Native PWA Header (iOS/Android style) --}}
{{-- Shows only in PWA mode, replaces web navigation --}}

@props([
    'title' => '',
    'backUrl' => null,
    'backText' => 'Назад',
    'showProfile' => false,
    'showMenu' => false
])

<header class="pwa-only native-header">
    {{-- Left Action --}}
    <div class="flex items-center">
        @if($backUrl)
            {{-- Back button (iOS style) --}}
            <a href="{{ $backUrl }}"
               class="native-header-btn"
               onclick="if(window.haptic) window.haptic.light()">
                <svg class="w-6 h-6 pwa-ios:block pwa-android:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <svg class="w-6 h-6 pwa-android:block pwa-ios:hidden hidden" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                </svg>
                <span class="pwa-ios:inline pwa-android:hidden">{{ $backText }}</span>
            </a>
        @elseif($showMenu)
            {{-- Menu button --}}
            <button @click="$store.ui.toggleSidebar()"
                    class="native-header-btn"
                    onclick="if(window.haptic) window.haptic.light()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        @endif
    </div>

    {{-- Title --}}
    <h1 class="native-header-title">{{ $title }}</h1>

    {{-- Right Action --}}
    <div class="flex items-center">
        @if($showProfile)
            {{-- Profile button --}}
            <a href="/settings"
               class="native-header-btn"
               onclick="if(window.haptic) window.haptic.light()">
                <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center">
                    <span class="text-white text-sm font-semibold" x-text="$store.auth.user?.name?.charAt(0) || 'U'"></span>
                </div>
            </a>
        @else
            {{-- Spacer to keep title centered --}}
            <div class="w-11"></div>
        @endif

        {{ $slot }}
    </div>
</header>

{{-- Alternative: Large Title Header (iOS style for main pages) --}}
@if(isset($largeTitle))
<header class="pwa-only bg-white" style="padding-top: env(safe-area-inset-top);">
    <div class="px-4 pt-12 pb-4">
        <h1 class="native-title">{{ $largeTitle }}</h1>
    </div>
</header>
@endif
