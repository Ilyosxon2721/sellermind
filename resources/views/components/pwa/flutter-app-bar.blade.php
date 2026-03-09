{{--
    Flutter-style App Bar
    Минималистичный навбар в стиле Flutter

    @props
    - title: string - Заголовок
    - subtitle: string - Подзаголовок (название компании)
    - showAvatar: bool - Показывать аватар
    - showNotifications: bool - Показывать уведомления
    - notificationCount: int - Количество уведомлений
--}}

@props([
    'title' => 'SellerMind',
    'subtitle' => null,
    'showAvatar' => true,
    'showNotifications' => true,
    'notificationCount' => 0,
])

<header
    x-data="flutterAppBar()"
    class="fixed top-0 inset-x-0 z-50 bg-white"
    style="padding-top: env(safe-area-inset-top, 0px);"
>
    <div class="h-14 px-4 flex items-center justify-between border-b border-gray-100">
        {{-- Left: Avatar + Title --}}
        <div class="flex items-center space-x-3 min-w-0 flex-1">
            @if($showAvatar)
            <a href="/profile" class="flex-shrink-0">
                <div
                    class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-semibold shadow-sm"
                    :style="'background: ' + avatarColor"
                    x-text="avatarInitial"
                ></div>
            </a>
            @endif

            <div class="min-w-0 flex-1">
                <h1 class="text-lg font-bold text-gray-900 truncate">{{ $title }}</h1>
                @if($subtitle)
                <p class="text-xs text-gray-500 truncate flex items-center">
                    {{ $subtitle }}
                    <svg class="w-3 h-3 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </p>
                @else
                <button
                    @click="showCompanySelector = true"
                    class="text-xs text-gray-500 truncate flex items-center hover:text-gray-700"
                >
                    <span x-text="$store.auth?.currentCompany?.name || 'Выбрать компанию'"></span>
                    <svg class="w-3 h-3 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                @endif
            </div>
        </div>

        {{-- Right: Actions --}}
        <div class="flex items-center space-x-2">
            @if($showNotifications)
            <a
                href="/notifications"
                class="relative p-2 rounded-full hover:bg-gray-100 active:bg-gray-200 transition-colors"
                onclick="if(window.haptic) window.haptic.light()"
            >
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                </svg>
                @if($notificationCount > 0)
                <span class="absolute -top-0.5 -right-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white">
                    {{ $notificationCount > 99 ? '99+' : $notificationCount }}
                </span>
                @endif
            </a>
            @endif

            <a
                href="/settings"
                class="p-2 rounded-full hover:bg-gray-100 active:bg-gray-200 transition-colors"
                onclick="if(window.haptic) window.haptic.light()"
            >
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </a>
        </div>
    </div>
</header>

{{-- Spacer --}}
<div class="h-14" style="padding-top: env(safe-area-inset-top, 0px);"></div>

<script>
function flutterAppBar() {
    return {
        showCompanySelector: false,

        get avatarInitial() {
            const name = this.$store?.auth?.user?.name || 'U';
            return name.charAt(0).toUpperCase();
        },

        get avatarColor() {
            const colors = [
                '#3B82F6', '#10B981', '#F59E0B', '#EF4444',
                '#8B5CF6', '#EC4899', '#06B6D4', '#F97316'
            ];
            const name = this.$store?.auth?.user?.name || 'U';
            const index = name.charCodeAt(0) % colors.length;
            return colors[index];
        }
    };
}
</script>
