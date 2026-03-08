{{--
    PWA Bottom Tab Bar Component

    5 tabs: Home, Products, Add (center FAB), Analytics, Profile
    Only visible in PWA standalone mode
--}}

<nav
    x-data="pwaTabBar()"
    x-cloak
    class="pwa-only sm-pwa-tabbar"
    @keydown.escape.window="closeSheet()"
>
    {{-- Tab Bar Container --}}
    <div class="sm-pwa-tabbar-inner">
        {{-- Tab: Home --}}
        <a
            href="{{ route('dashboard') }}"
            class="sm-pwa-tab"
            :class="{ 'active': isActive('dashboard', ['home', 'dashboard']) }"
            @click="haptic()"
        >
            <span class="sm-pwa-tab-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                    <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" />
                    <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.432z" />
                </svg>
            </span>
            <span class="sm-pwa-tab-label">{{ __('Главная') }}</span>
        </a>

        {{-- Tab: Products --}}
        <a
            href="{{ route('web.products.index') }}"
            class="sm-pwa-tab"
            :class="{ 'active': isActive('products') }"
            @click="haptic()"
        >
            <span class="sm-pwa-tab-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                    <path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375z" />
                    <path fill-rule="evenodd" d="M3.087 9l.54 9.176A3 3 0 006.62 21h10.757a3 3 0 002.995-2.824L20.913 9H3.087zm6.163 3.75A.75.75 0 0110 12h4a.75.75 0 010 1.5h-4a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                </svg>
            </span>
            <span class="sm-pwa-tab-label">{{ __('Товары') }}</span>
        </a>

        {{-- Tab: Add (Center FAB Button) --}}
        <button
            type="button"
            class="sm-pwa-tab-fab"
            @click="toggleSheet(); hapticHeavy()"
            :class="{ 'active': sheetOpen }"
        >
            <span class="sm-pwa-tab-fab-inner">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    class="w-7 h-7 transition-transform duration-200"
                    :class="{ 'rotate-45': sheetOpen }"
                >
                    <path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd" />
                </svg>
            </span>
            <span class="sm-pwa-tab-label">{{ __('Добавить') }}</span>
        </button>

        {{-- Tab: Analytics --}}
        <a
            href="{{ route('analytics') }}"
            class="sm-pwa-tab"
            :class="{ 'active': isActive('analytics') }"
            @click="haptic()"
        >
            <span class="sm-pwa-tab-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                    <path fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5a.75.75 0 01-.75-.75V3z" clip-rule="evenodd" />
                </svg>
            </span>
            <span class="sm-pwa-tab-label">{{ __('Аналитика') }}</span>
        </a>

        {{-- Tab: Profile/Settings --}}
        <a
            href="{{ route('settings') }}"
            class="sm-pwa-tab"
            :class="{ 'active': isActive('settings', ['profile', 'settings']) }"
            @click="haptic()"
        >
            <span class="sm-pwa-tab-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                    <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
                </svg>
            </span>
            <span class="sm-pwa-tab-label">{{ __('Профиль') }}</span>
        </a>
    </div>

    {{-- Quick Actions Bottom Sheet --}}
    <div
        x-show="sheetOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="sm-pwa-sheet-backdrop"
        @click="closeSheet()"
    ></div>

    <div
        x-show="sheetOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="sm-pwa-sheet"
        @click.outside="closeSheet()"
    >
        {{-- Sheet Handle --}}
        <div class="sm-pwa-sheet-handle"></div>

        {{-- Sheet Title --}}
        <h3 class="sm-pwa-sheet-title">{{ __('Создать') }}</h3>

        {{-- Quick Action Items --}}
        <div class="sm-pwa-sheet-actions">
            <a href="{{ route('web.products.create') }}" class="sm-pwa-sheet-action" @click="haptic()">
                <span class="sm-pwa-sheet-action-icon bg-blue-100 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375z" />
                        <path fill-rule="evenodd" d="M3.087 9l.54 9.176A3 3 0 006.62 21h10.757a3 3 0 002.995-2.824L20.913 9H3.087zm6.163 3.75A.75.75 0 0110 12h4a.75.75 0 010 1.5h-4a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                    </svg>
                </span>
                <span class="sm-pwa-sheet-action-text">
                    <span class="sm-pwa-sheet-action-title">{{ __('Новый товар') }}</span>
                    <span class="sm-pwa-sheet-action-desc">{{ __('Добавить товар в каталог') }}</span>
                </span>
                <span class="sm-pwa-sheet-action-chevron">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                </span>
            </a>

            <a href="{{ route('sales.create') }}" class="sm-pwa-sheet-action" @click="haptic()">
                <span class="sm-pwa-sheet-action-icon bg-green-100 text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path d="M10.464 8.746c.227-.18.497-.311.786-.394v2.795a2.252 2.252 0 01-.786-.393c-.394-.313-.546-.681-.546-1.004 0-.323.152-.691.546-1.004zM12.75 15.662v-2.824c.347.085.664.228.921.421.427.32.579.686.579.991 0 .305-.152.671-.579.991a2.534 2.534 0 01-.921.42z" />
                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v.816a3.836 3.836 0 00-1.72.756c-.712.566-1.112 1.35-1.112 2.178 0 .829.4 1.612 1.113 2.178.502.4 1.102.647 1.719.756v2.978a2.536 2.536 0 01-.921-.421l-.879-.66a.75.75 0 00-.9 1.2l.879.66c.533.4 1.169.645 1.821.75V18a.75.75 0 001.5 0v-.81a4.124 4.124 0 001.821-.749c.745-.559 1.179-1.344 1.179-2.191 0-.847-.434-1.632-1.179-2.191a4.122 4.122 0 00-1.821-.75V8.354c.29.082.559.213.786.393l.415.33a.75.75 0 00.933-1.175l-.415-.33a3.836 3.836 0 00-1.719-.755V6z" clip-rule="evenodd" />
                    </svg>
                </span>
                <span class="sm-pwa-sheet-action-text">
                    <span class="sm-pwa-sheet-action-title">{{ __('Новая продажа') }}</span>
                    <span class="sm-pwa-sheet-action-desc">{{ __('Оформить продажу') }}</span>
                </span>
                <span class="sm-pwa-sheet-action-chevron">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                </span>
            </a>

            <a href="{{ route('warehouse.in.create') }}" class="sm-pwa-sheet-action" @click="haptic()">
                <span class="sm-pwa-sheet-action-icon bg-purple-100 text-purple-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path d="M3.375 4.5C2.339 4.5 1.5 5.34 1.5 6.375V13.5h12V6.375c0-1.036-.84-1.875-1.875-1.875h-8.25zM13.5 15h-12v2.625c0 1.035.84 1.875 1.875 1.875h.375a3 3 0 116 0h3a.75.75 0 00.75-.75V15z" />
                        <path d="M8.25 19.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0zM15.75 6.75a.75.75 0 00-.75.75v11.25c0 .087.015.17.042.248a3 3 0 015.958.464c.853-.175 1.5-.935 1.5-1.837V8.25a1.5 1.5 0 00-1.5-1.5h-5.25z" />
                        <path d="M19.5 19.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z" />
                    </svg>
                </span>
                <span class="sm-pwa-sheet-action-text">
                    <span class="sm-pwa-sheet-action-title">{{ __('Приход товара') }}</span>
                    <span class="sm-pwa-sheet-action-desc">{{ __('Оформить поступление') }}</span>
                </span>
                <span class="sm-pwa-sheet-action-chevron">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                </span>
            </a>

            <a href="{{ route('warehouse.write-off.create') }}" class="sm-pwa-sheet-action" @click="haptic()">
                <span class="sm-pwa-sheet-action-icon bg-orange-100 text-orange-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 013.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 013.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 01-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875zm5.845 17.03a.75.75 0 001.06 0l3-3a.75.75 0 10-1.06-1.06l-1.72 1.72V12a.75.75 0 00-1.5 0v4.19l-1.72-1.72a.75.75 0 00-1.06 1.06l3 3z" clip-rule="evenodd" />
                        <path d="M14.25 5.25a5.23 5.23 0 00-1.279-3.434 9.768 9.768 0 016.963 6.963A5.23 5.23 0 0016.5 7.5h-1.875a.375.375 0 01-.375-.375V5.25z" />
                    </svg>
                </span>
                <span class="sm-pwa-sheet-action-text">
                    <span class="sm-pwa-sheet-action-title">{{ __('Списание') }}</span>
                    <span class="sm-pwa-sheet-action-desc">{{ __('Списать товар со склада') }}</span>
                </span>
                <span class="sm-pwa-sheet-action-chevron">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                </span>
            </a>

            <a href="{{ route('marketplace.index') }}?action=create" class="sm-pwa-sheet-action" @click="haptic()">
                <span class="sm-pwa-sheet-action-icon bg-pink-100 text-pink-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path d="M5.223 2.25c-.497 0-.974.198-1.325.55l-1.3 1.298A3.75 3.75 0 007.5 9.75c.627.47 1.406.75 2.25.75.844 0 1.624-.28 2.25-.75.626.47 1.406.75 2.25.75.844 0 1.623-.28 2.25-.75a3.75 3.75 0 004.902-5.652l-1.3-1.299a1.875 1.875 0 00-1.325-.549H5.223z" />
                        <path fill-rule="evenodd" d="M3 20.25v-8.755c1.42.674 3.08.673 4.5 0A5.234 5.234 0 009.75 12c.804 0 1.568-.182 2.25-.506a5.234 5.234 0 002.25.506c.804 0 1.567-.182 2.25-.506 1.42.674 3.08.675 4.5.001v8.755h.75a.75.75 0 010 1.5H2.25a.75.75 0 010-1.5H3zm3-6a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-3a.75.75 0 01-.75-.75v-3zm8.25-.75a.75.75 0 00-.75.75v5.25c0 .414.336.75.75.75h3a.75.75 0 00.75-.75v-5.25a.75.75 0 00-.75-.75h-3z" clip-rule="evenodd" />
                    </svg>
                </span>
                <span class="sm-pwa-sheet-action-text">
                    <span class="sm-pwa-sheet-action-title">{{ __('Добавить маркетплейс') }}</span>
                    <span class="sm-pwa-sheet-action-desc">{{ __('Подключить новый аккаунт') }}</span>
                </span>
                <span class="sm-pwa-sheet-action-chevron">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                </span>
            </a>
        </div>

        {{-- Cancel Button --}}
        <button
            type="button"
            class="sm-pwa-sheet-cancel"
            @click="closeSheet(); haptic()"
        >
            {{ __('Отмена') }}
        </button>
    </div>
</nav>

{{-- Component Styles --}}
<style>
    /* PWA Tab Bar - Base Container */
    .sm-pwa-tabbar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 100;
        padding-bottom: env(safe-area-inset-bottom, 0px);
    }

    .sm-pwa-tabbar-inner {
        display: flex;
        align-items: flex-start;
        justify-content: space-around;
        height: 56px;
        padding-top: 6px;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-top: 0.5px solid rgba(0, 0, 0, 0.1);
    }

    /* Individual Tab */
    .sm-pwa-tab {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4px 12px;
        text-decoration: none;
        position: relative;
        transition: transform 0.1s ease;
        -webkit-tap-highlight-color: transparent;
    }

    .sm-pwa-tab:active {
        transform: scale(0.92);
    }

    .sm-pwa-tab-icon {
        width: 24px;
        height: 24px;
        margin-bottom: 2px;
        color: #8E8E93;
        transition: color 0.15s ease;
    }

    .sm-pwa-tab-icon svg {
        width: 100%;
        height: 100%;
    }

    .sm-pwa-tab.active .sm-pwa-tab-icon {
        color: #007AFF;
    }

    .sm-pwa-tab-label {
        font-size: 10px;
        font-weight: 400;
        color: #8E8E93;
        transition: color 0.15s ease;
    }

    .sm-pwa-tab.active .sm-pwa-tab-label {
        color: #007AFF;
        font-weight: 500;
    }

    /* Center FAB Button */
    .sm-pwa-tab-fab {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0;
        background: none;
        border: none;
        cursor: pointer;
        margin-top: -16px;
        -webkit-tap-highlight-color: transparent;
        position: relative;
    }

    .sm-pwa-tab-fab-inner {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: linear-gradient(135deg, #007AFF 0%, #5856D6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        box-shadow: 0 4px 16px rgba(0, 122, 255, 0.4);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .sm-pwa-tab-fab:active .sm-pwa-tab-fab-inner {
        transform: scale(0.92);
        box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
    }

    .sm-pwa-tab-fab.active .sm-pwa-tab-fab-inner {
        background: linear-gradient(135deg, #FF3B30 0%, #FF9500 100%);
    }

    .sm-pwa-tab-fab .sm-pwa-tab-label {
        margin-top: 4px;
        color: #007AFF;
        font-weight: 500;
    }

    .sm-pwa-tab-fab.active .sm-pwa-tab-label {
        color: #FF3B30;
    }

    /* Bottom Sheet Backdrop */
    .sm-pwa-sheet-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        z-index: 150;
    }

    /* Bottom Sheet Container */
    .sm-pwa-sheet {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #FFFFFF;
        border-radius: 20px 20px 0 0;
        padding: 12px 16px;
        padding-bottom: calc(16px + env(safe-area-inset-bottom, 0px));
        z-index: 160;
        max-height: 70vh;
        overflow-y: auto;
    }

    /* Sheet Handle */
    .sm-pwa-sheet-handle {
        width: 36px;
        height: 4px;
        background: #E5E5EA;
        border-radius: 2px;
        margin: 0 auto 16px;
    }

    /* Sheet Title */
    .sm-pwa-sheet-title {
        font-size: 20px;
        font-weight: 600;
        color: #1C1C1E;
        margin: 0 0 16px;
        text-align: center;
    }

    /* Sheet Actions Container */
    .sm-pwa-sheet-actions {
        display: flex;
        flex-direction: column;
        gap: 2px;
        background: #F2F2F7;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 12px;
    }

    /* Individual Action Item */
    .sm-pwa-sheet-action {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        background: #FFFFFF;
        text-decoration: none;
        transition: background 0.15s ease;
        -webkit-tap-highlight-color: transparent;
    }

    .sm-pwa-sheet-action:active {
        background: #F2F2F7;
    }

    .sm-pwa-sheet-action-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sm-pwa-sheet-action-icon svg {
        width: 24px;
        height: 24px;
    }

    .sm-pwa-sheet-action-text {
        flex: 1;
        min-width: 0;
    }

    .sm-pwa-sheet-action-title {
        display: block;
        font-size: 16px;
        font-weight: 500;
        color: #1C1C1E;
    }

    .sm-pwa-sheet-action-desc {
        display: block;
        font-size: 13px;
        color: #8E8E93;
        margin-top: 2px;
    }

    .sm-pwa-sheet-action-chevron {
        color: #C7C7CC;
        flex-shrink: 0;
    }

    /* Cancel Button */
    .sm-pwa-sheet-cancel {
        width: 100%;
        padding: 16px;
        background: #FFFFFF;
        border: none;
        border-radius: 12px;
        font-size: 17px;
        font-weight: 600;
        color: #007AFF;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        transition: background 0.15s ease;
    }

    .sm-pwa-sheet-cancel:active {
        background: #F2F2F7;
    }

    /* Tailwind rotate utility for plus icon */
    .rotate-45 {
        transform: rotate(45deg);
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .sm-pwa-tabbar-inner {
            background: rgba(28, 28, 30, 0.92);
            border-top-color: rgba(255, 255, 255, 0.1);
        }

        .sm-pwa-tab-icon {
            color: #8E8E93;
        }

        .sm-pwa-tab-label {
            color: #8E8E93;
        }

        .sm-pwa-sheet {
            background: #1C1C1E;
        }

        .sm-pwa-sheet-handle {
            background: #48484A;
        }

        .sm-pwa-sheet-title {
            color: #FFFFFF;
        }

        .sm-pwa-sheet-actions {
            background: #2C2C2E;
        }

        .sm-pwa-sheet-action {
            background: #1C1C1E;
        }

        .sm-pwa-sheet-action:active {
            background: #2C2C2E;
        }

        .sm-pwa-sheet-action-title {
            color: #FFFFFF;
        }

        .sm-pwa-sheet-action-desc {
            color: #8E8E93;
        }

        .sm-pwa-sheet-cancel {
            background: #2C2C2E;
        }

        .sm-pwa-sheet-cancel:active {
            background: #3A3A3C;
        }
    }

    /* iOS specific adjustments */
    @supports (-webkit-touch-callout: none) {
        .sm-pwa-tabbar {
            padding-bottom: max(env(safe-area-inset-bottom, 0px), 20px);
        }
    }
</style>

{{-- Alpine.js Component Script --}}
<script>
    function pwaTabBar() {
        return {
            sheetOpen: false,

            init() {
                // Close sheet on navigation
                window.addEventListener('popstate', () => {
                    this.sheetOpen = false;
                });
            },

            isActive(routeName, aliases = []) {
                const currentPath = window.location.pathname;
                const routes = [routeName, ...aliases];

                return routes.some(route => {
                    if (route === 'dashboard' || route === 'home') {
                        return currentPath === '/' ||
                               currentPath === '/home' ||
                               currentPath === '/dashboard';
                    }
                    return currentPath.includes(`/${route}`);
                });
            },

            toggleSheet() {
                this.sheetOpen = !this.sheetOpen;

                // Prevent body scroll when sheet is open
                if (this.sheetOpen) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            },

            closeSheet() {
                this.sheetOpen = false;
                document.body.style.overflow = '';
            },

            haptic() {
                if (window.SmHaptic) {
                    window.SmHaptic.light();
                }
            },

            hapticHeavy() {
                if (window.SmHaptic) {
                    window.SmHaptic.heavy();
                }
            }
        };
    }
</script>
