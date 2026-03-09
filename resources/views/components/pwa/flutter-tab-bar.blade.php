{{--
    Flutter-style Bottom Tab Bar
    С центральной FAB кнопкой "Добавить"

    5 tabs: Главная, Товары, [+], Аналитика, Профиль
--}}

@props([
    'badges' => [],
])

<nav
    x-data="flutterTabBar()"
    class="fixed inset-x-0 bottom-0 z-50 bg-white border-t border-gray-100"
    style="padding-bottom: env(safe-area-inset-bottom, 0px);"
>
    <div class="flex h-16 items-center justify-around relative">
        {{-- Tab: Главная --}}
        <a
            href="/dashboard-flutter"
            class="flex flex-col items-center justify-center w-16 py-2 transition-colors"
            :class="activeTab === 'home' ? 'text-blue-600' : 'text-gray-400'"
            @click="handleTap('home')"
        >
            <svg class="w-6 h-6" :fill="activeTab === 'home' ? 'currentColor' : 'none'" :stroke="activeTab === 'home' ? 'none' : 'currentColor'" stroke-width="1.5" viewBox="0 0 24 24">
                <template x-if="activeTab === 'home'">
                    <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z"/>
                </template>
                <template x-if="activeTab !== 'home'">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                </template>
            </svg>
            <span class="text-[10px] font-medium mt-1">Главная</span>
        </a>

        {{-- Tab: Товары --}}
        <a
            href="/products-pwa"
            class="flex flex-col items-center justify-center w-16 py-2 transition-colors"
            :class="activeTab === 'products' ? 'text-blue-600' : 'text-gray-400'"
            @click="handleTap('products')"
        >
            @if(isset($badges['products']) && $badges['products'] > 0)
            <span class="absolute -top-1 right-1/4 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                {{ $badges['products'] > 99 ? '99+' : $badges['products'] }}
            </span>
            @endif
            <svg class="w-6 h-6" :fill="activeTab === 'products' ? 'currentColor' : 'none'" :stroke="activeTab === 'products' ? 'none' : 'currentColor'" stroke-width="1.5" viewBox="0 0 24 24">
                <template x-if="activeTab === 'products'">
                    <path d="M12.378 1.602a.75.75 0 0 0-.756 0L3 6.632l9 5.25 9-5.25-8.622-5.03ZM21.75 7.93l-9 5.25v9l8.628-5.032a.75.75 0 0 0 .372-.648V7.93ZM11.25 22.18v-9l-9-5.25v8.57a.75.75 0 0 0 .372.648l8.628 5.033Z"/>
                </template>
                <template x-if="activeTab !== 'products'">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                </template>
            </svg>
            <span class="text-[10px] font-medium mt-1">Товары</span>
        </a>

        {{-- Center: FAB Button --}}
        <div class="flex flex-col items-center justify-center w-16">
            <button
                @click="showAddMenu = true; haptic()"
                class="w-14 h-14 -mt-7 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/30 flex items-center justify-center active:scale-95 transition-transform"
            >
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
            </button>
            <span class="text-[10px] font-medium text-gray-400 mt-1">Добавить</span>
        </div>

        {{-- Tab: Аналитика --}}
        <a
            href="/analytics/pwa"
            class="flex flex-col items-center justify-center w-16 py-2 transition-colors"
            :class="activeTab === 'analytics' ? 'text-blue-600' : 'text-gray-400'"
            @click="handleTap('analytics')"
        >
            <svg class="w-6 h-6" :fill="activeTab === 'analytics' ? 'currentColor' : 'none'" :stroke="activeTab === 'analytics' ? 'none' : 'currentColor'" stroke-width="1.5" viewBox="0 0 24 24">
                <template x-if="activeTab === 'analytics'">
                    <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75ZM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 0 1-1.875-1.875V8.625ZM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 0 1 3 19.875v-6.75Z"/>
                </template>
                <template x-if="activeTab !== 'analytics'">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                </template>
            </svg>
            <span class="text-[10px] font-medium mt-1">Аналитика</span>
        </a>

        {{-- Tab: Профиль --}}
        <a
            href="/profile-pwa"
            class="flex flex-col items-center justify-center w-16 py-2 transition-colors"
            :class="activeTab === 'profile' ? 'text-blue-600' : 'text-gray-400'"
            @click="handleTap('profile')"
        >
            <svg class="w-6 h-6" :fill="activeTab === 'profile' ? 'currentColor' : 'none'" :stroke="activeTab === 'profile' ? 'none' : 'currentColor'" stroke-width="1.5" viewBox="0 0 24 24">
                <template x-if="activeTab === 'profile'">
                    <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"/>
                </template>
                <template x-if="activeTab !== 'profile'">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                </template>
            </svg>
            <span class="text-[10px] font-medium mt-1">Профиль</span>
        </a>
    </div>

    {{-- Add Menu Sheet --}}
    <template x-teleport="body">
        <div
            x-show="showAddMenu"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="showAddMenu = false"
            class="fixed inset-0 bg-black/40 z-50"
        ></div>
        <div
            x-show="showAddMenu"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="translate-y-full"
            class="fixed inset-x-0 bottom-0 z-50 bg-white rounded-t-3xl"
            style="padding-bottom: env(safe-area-inset-bottom, 0px);"
        >
            {{-- Handle --}}
            <div class="flex justify-center py-3">
                <div class="w-10 h-1 bg-gray-300 rounded-full"></div>
            </div>

            <div class="px-6 pb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Быстрые действия</h3>

                <div class="grid grid-cols-3 gap-4">
                    {{-- Добавить товар --}}
                    <a href="/products/create" class="flex flex-col items-center p-4 rounded-2xl bg-blue-50 hover:bg-blue-100 transition-colors" @click="showAddMenu = false">
                        <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-700 text-center">Товар</span>
                    </a>

                    {{-- Приход --}}
                    <a href="/warehouse/in/create" class="flex flex-col items-center p-4 rounded-2xl bg-green-50 hover:bg-green-100 transition-colors" @click="showAddMenu = false">
                        <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m0-16l-4 4m4-4l4 4"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-700 text-center">Приход</span>
                    </a>

                    {{-- Продажа --}}
                    <a href="/sales/create" class="flex flex-col items-center p-4 rounded-2xl bg-purple-50 hover:bg-purple-100 transition-colors" @click="showAddMenu = false">
                        <div class="w-12 h-12 rounded-full bg-purple-500 flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-700 text-center">Продажа</span>
                    </a>

                    {{-- Списание --}}
                    <a href="/warehouse/write-off/create" class="flex flex-col items-center p-4 rounded-2xl bg-red-50 hover:bg-red-100 transition-colors" @click="showAddMenu = false">
                        <div class="w-12 h-12 rounded-full bg-red-500 flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-700 text-center">Списание</span>
                    </a>

                    {{-- Синхронизация --}}
                    <button @click="syncAll(); showAddMenu = false" class="flex flex-col items-center p-4 rounded-2xl bg-orange-50 hover:bg-orange-100 transition-colors">
                        <div class="w-12 h-12 rounded-full bg-orange-500 flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-700 text-center">Синхро</span>
                    </button>

                    {{-- AI Chat --}}
                    <a href="/chat-pwa" class="flex flex-col items-center p-4 rounded-2xl bg-indigo-50 hover:bg-indigo-100 transition-colors" @click="showAddMenu = false">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-700 text-center">AI</span>
                    </a>
                </div>
            </div>
        </div>
    </template>
</nav>

<script>
function flutterTabBar() {
    return {
        activeTab: 'home',
        showAddMenu: false,

        init() {
            this.activeTab = this.getActiveTabFromPath();
            window.addEventListener('popstate', () => {
                this.activeTab = this.getActiveTabFromPath();
            });
        },

        getActiveTabFromPath() {
            const path = window.location.pathname;
            if (path === '/' || path.startsWith('/dashboard')) return 'home';
            if (path.startsWith('/products')) return 'products';
            if (path.startsWith('/analytics')) return 'analytics';
            if (path.startsWith('/profile') || path.startsWith('/settings')) return 'profile';
            if (path.startsWith('/chat')) return 'chat';
            return 'home';
        },

        handleTap(tab) {
            this.haptic();
            this.activeTab = tab;
        },

        haptic() {
            if (window.haptic) window.haptic.light();
            else if (window.SmHaptic) window.SmHaptic.light();
            else if (navigator.vibrate) navigator.vibrate(10);
        },

        async syncAll() {
            this.haptic();
            if (window.toast) window.toast.info('Синхронизация запущена...');
            try {
                await fetch('/api/marketplace/sync/all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    }
                });
                if (window.toast) window.toast.success('Синхронизация запущена');
            } catch (e) {
                if (window.toast) window.toast.error('Ошибка синхронизации');
            }
        }
    };
}
</script>
