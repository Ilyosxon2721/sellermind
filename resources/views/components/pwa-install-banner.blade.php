{{-- PWA Install Banner - Shows when app can be installed --}}
{{-- Only shows in browser mode (not in installed PWA) --}}

<div
    x-data="pwaInstallBanner()"
    x-show="showBanner && !isPWAInstalled"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-full"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-full"
    x-cloak
    class="fixed bottom-0 left-0 right-0 z-[100]"
    style="padding-bottom: env(safe-area-inset-bottom, 16px);"
>
    {{-- Backdrop blur overlay --}}
    <div class="mx-4 mb-4">
        <div class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden">

            {{-- Main Content --}}
            <div class="p-4">
                <div class="flex items-start gap-4">
                    {{-- App Icon --}}
                    <div class="flex-shrink-0">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Text Content --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Установите SellerMind
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Добавьте приложение на главный экран для быстрого доступа
                        </p>

                        {{-- Features List --}}
                        <div class="flex flex-wrap gap-2 mt-3">
                            <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Работает офлайн
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Push-уведомления
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Быстрый запуск
                            </span>
                        </div>
                    </div>

                    {{-- Close Button --}}
                    <button
                        @click="dismiss()"
                        class="flex-shrink-0 p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="px-4 pb-4 flex gap-3">
                <button
                    @click="dismiss()"
                    class="flex-1 py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    Позже
                </button>
                <button
                    @click="install()"
                    class="flex-1 py-3 px-4 text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl hover:from-blue-600 hover:to-blue-700 shadow-lg shadow-blue-500/25 transition-all flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Установить
                </button>
            </div>

            {{-- iOS Instructions (shown only on iOS in browser) --}}
            <div
                x-show="isIOS && showIOSInstructions"
                x-transition
                class="px-4 pb-4"
            >
                <div class="bg-blue-50 dark:bg-blue-900/30 rounded-xl p-4">
                    <p class="text-sm text-blue-800 dark:text-blue-200 font-medium mb-2">
                        Как установить на iPhone/iPad:
                    </p>
                    <ol class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
                        <li class="flex items-center gap-2">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-blue-200 dark:bg-blue-800 flex items-center justify-center text-blue-700 dark:text-blue-200 font-medium text-xs">1</span>
                            Нажмите кнопку "Поделиться" внизу экрана
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-blue-200 dark:bg-blue-800 flex items-center justify-center text-blue-700 dark:text-blue-200 font-medium text-xs">2</span>
                            Прокрутите и выберите "На экран Домой"
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-blue-200 dark:bg-blue-800 flex items-center justify-center text-blue-700 dark:text-blue-200 font-medium text-xs">3</span>
                            Нажмите "Добавить" в правом верхнем углу
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function pwaInstallBanner() {
    return {
        showBanner: false,
        isPWAInstalled: window.isPWAInstalled || false,
        isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent),
        canInstallNatively: false,
        deferredPrompt: null,
        showIOSInstructions: false,

        init() {
            // Не показывать в PWA режиме
            if (this.isPWAInstalled || window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
                this.isPWAInstalled = true;
                return;
            }

            // Проверяем, не был ли баннер закрыт (7 дней)
            const dismissed = localStorage.getItem('pwa_install_dismissed');
            if (dismissed) {
                const daysSince = (Date.now() - parseInt(dismissed)) / (1000 * 60 * 60 * 24);
                if (daysSince < 7) {
                    return;
                }
            }

            // Слушаем beforeinstallprompt (Android/Chrome)
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                this.canInstallNatively = true;
                this.showBannerWithDelay();
            });

            // Для iOS Safari показываем баннер с инструкциями
            if (this.isIOS && this.isSafari()) {
                this.showBannerWithDelay();
            }

            // Для десктопных браузеров без beforeinstallprompt — показываем через 3 сек
            if (!this.isIOS) {
                setTimeout(() => {
                    if (!this.showBanner && !this.canInstallNatively && !this.isPWAInstalled) {
                        this.showBannerWithDelay();
                    }
                }, 1000);
            }

            // Отслеживаем установку
            window.addEventListener('appinstalled', () => {
                this.showBanner = false;
                this.isPWAInstalled = true;
                localStorage.removeItem('pwa_install_dismissed');

                if (window.SmHaptic) {
                    window.SmHaptic.success();
                }
            });

            // Отслеживаем изменение display-mode
            window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
                this.isPWAInstalled = e.matches;
                if (e.matches) {
                    this.showBanner = false;
                }
            });
        },

        isSafari() {
            return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        },

        showBannerWithDelay() {
            setTimeout(() => {
                if (!document.hidden && !this.isPWAInstalled) {
                    this.showBanner = true;

                    if (window.SmHaptic) {
                        window.SmHaptic.light();
                    }
                }
            }, 3000);
        },

        async install() {
            if (this.deferredPrompt) {
                // Нативный промпт установки (Android/Chrome)
                try {
                    this.deferredPrompt.prompt();
                    const { outcome } = await this.deferredPrompt.userChoice;
                    if (outcome === 'accepted') {
                        this.showBanner = false;
                    }
                } catch (e) {
                    console.warn('Install prompt failed:', e);
                } finally {
                    this.deferredPrompt = null;
                }
            } else if (this.isIOS) {
                // Показываем инструкции для iOS
                this.showIOSInstructions = true;
                if (window.SmHaptic) {
                    window.SmHaptic.medium();
                }
            } else {
                // Десктопный браузер без нативного промпта — скрываем баннер и запоминаем
                this.dismiss();
            }
        },

        dismiss() {
            this.showBanner = false;
            localStorage.setItem('pwa_install_dismissed', Date.now().toString());

            if (window.SmHaptic) {
                window.SmHaptic.light();
            }
        }
    };
}
</script>
