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
    <div class="native-header-left">
        @if($backUrl)
            {{-- Back button (iOS style) --}}
            <a href="{{ $backUrl }}"
               class="native-header-btn"
               onclick="if(window.haptic) window.haptic.light()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
        @elseif($showProfile)
            {{-- Profile Avatar --}}
            <a href="/settings"
               class="native-header-avatar"
               onclick="if(window.haptic) window.haptic.light()">
                <span x-text="$store.auth.user?.name?.charAt(0) || 'U'"></span>
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
        @else
            <div class="w-10"></div>
        @endif
    </div>

    {{-- Center Title --}}
    <h1 class="native-header-title">{{ $title }}</h1>

    {{-- Right Actions --}}
    <div class="native-header-right">
        {{ $slot }}
        @if($slot->isEmpty())
            @if($showProfile)
                <div class="w-10"></div>
            @else
                <div class="w-10"></div>
            @endif
        @endif
    </div>
</header>

<style>
/* PWA Header Styles */
.pwa-mode .native-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: calc(44px + env(safe-area-inset-top, 0px));
    padding: 0 calc(20px + env(safe-area-inset-right, 0px)) 0 calc(20px + env(safe-area-inset-left, 0px));
    padding-top: env(safe-area-inset-top, 0px);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
}

.pwa-mode .native-header-left,
.pwa-mode .native-header-right {
    display: flex;
    align-items: center;
    min-width: 60px;
}

.pwa-mode .native-header-left {
    justify-content: flex-start;
}

.pwa-mode .native-header-right {
    justify-content: flex-end;
}

.pwa-mode .native-header-title {
    flex: 1;
    font-size: 17px;
    font-weight: 600;
    color: #000;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0 8px;
}

.pwa-mode .native-header-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    padding: 0;
    color: #007AFF;
    background: none;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
}

.pwa-mode .native-header-btn:active {
    opacity: 0.5;
    background: rgba(0, 122, 255, 0.1);
}

.pwa-mode .native-header-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #007AFF, #5856D6);
    border-radius: 50%;
    color: white;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    -webkit-tap-highlight-color: transparent;
}

.pwa-mode .native-header-avatar:active {
    opacity: 0.7;
    transform: scale(0.95);
}
</style>
