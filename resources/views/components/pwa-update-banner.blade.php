{{-- PWA Update Banner - Shows when a new version of the app is available --}}

<div x-data="pwaUpdateBanner()">
<div
    x-show="showUpdateBanner"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-full"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-full"
    class="fixed top-0 left-0 right-0 z-[250]"
    style="padding-top: env(safe-area-inset-top, 0px);"
>
    <div class="mx-4 mt-4">
        <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 rounded-2xl shadow-2xl overflow-hidden">
            {{-- Animated background --}}
            <div class="absolute inset-0 opacity-30">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent animate-shimmer"></div>
            </div>

            <div class="relative p-4">
                <div class="flex items-center gap-4">
                    {{-- Update Icon with Animation --}}
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center">
                            <svg class="w-7 h-7 text-white animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Text Content --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-white">
                            Доступно обновление
                        </h3>
                        <p class="text-sm text-white/80 mt-0.5">
                            Новая версия приложения готова к установке
                        </p>
                    </div>

                    {{-- Close Button --}}
                    <button
                        @click="dismissUpdate()"
                        class="flex-shrink-0 p-1.5 text-white/60 hover:text-white rounded-lg hover:bg-white/10 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-3 mt-4">
                    <button
                        @click="dismissUpdate()"
                        class="flex-1 py-2.5 px-4 text-sm font-medium text-white/80 bg-white/10 backdrop-blur rounded-xl hover:bg-white/20 transition-colors"
                    >
                        Позже
                    </button>
                    <button
                        @click="updateNow()"
                        :disabled="isUpdating"
                        class="flex-1 py-2.5 px-4 text-sm font-semibold text-indigo-600 bg-white rounded-xl hover:bg-gray-100 shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-70"
                    >
                        <template x-if="!isUpdating">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Обновить
                            </span>
                        </template>
                        <template x-if="isUpdating">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Обновление...
                            </span>
                        </template>
                    </button>
                </div>

                {{-- Version Info --}}
                <div x-show="newVersion" class="mt-3 text-center">
                    <span class="text-xs text-white/60">
                        Версия: <span class="font-mono" x-text="newVersion"></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Changelog Modal --}}
<div
    x-cloak
    x-show="showChangelog"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[260] flex items-end justify-center sm:items-center"
    @click.self="showChangelog = false"
>
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

    {{-- Modal Content --}}
    <div
        x-show="showChangelog"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95"
        class="relative bg-white dark:bg-gray-800 rounded-t-3xl sm:rounded-2xl w-full sm:max-w-md max-h-[80vh] overflow-hidden"
    >
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Что нового
                </h3>
                <button
                    @click="showChangelog = false"
                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Changelog Content --}}
        <div class="px-6 py-4 overflow-y-auto max-h-[50vh]">
            <template x-if="changelog.length > 0">
                <div class="space-y-4">
                    <template x-for="item in changelog" :key="item.version">
                        <div class="border-l-2 border-indigo-500 pl-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="item.version"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="item.date"></span>
                            </div>
                            <ul class="space-y-1">
                                <template x-for="change in item.changes" :key="change">
                                    <li class="text-sm text-gray-600 dark:text-gray-400 flex items-start gap-2">
                                        <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span x-text="change"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="changelog.length === 0">
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm">Список изменений недоступен</p>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
            <button
                @click="showChangelog = false; updateNow()"
                class="w-full py-3 px-4 text-sm font-semibold text-white bg-gradient-to-r from-indigo-500 to-purple-500 rounded-xl hover:from-indigo-600 hover:to-purple-600 transition-colors"
            >
                Обновить сейчас
            </button>
        </div>
    </div>
</div>
</div>

<style>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.animate-shimmer {
    animation: shimmer 2s infinite;
}
</style>

<script>
function pwaUpdateBanner() {
    return {
        showUpdateBanner: false,
        showChangelog: false,
        isUpdating: false,
        newVersion: null,
        currentVersion: null,
        changelog: [],

        init() {
            // Listen for SW update available event
            window.addEventListener('pwa:update-available', (e) => {
                this.newVersion = e.detail?.version || null;
                this.showUpdateBanner = true;

                // Haptic feedback
                if (window.SmHaptic) {
                    window.SmHaptic.medium();
                }

                // Load changelog if available
                this.loadChangelog();
            });

            // Listen for controller change (SW updated)
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.addEventListener('controllerchange', () => {
                    if (this.isUpdating) {
                        // Reload page after update
                        window.location.reload();
                    }
                });
            }
        },

        async loadChangelog() {
            try {
                const response = await fetch('/api/pwa/changelog');
                if (response.ok) {
                    const data = await response.json();
                    this.changelog = data.changelog || [];
                }
            } catch (e) {
                // Changelog not available
                this.changelog = [];
            }
        },

        async updateNow() {
            this.isUpdating = true;

            // Haptic feedback
            if (window.SmHaptic) {
                window.SmHaptic.success();
            }

            // Tell the waiting SW to activate
            if (window.updatePWA) {
                await window.updatePWA();
            } else {
                // Fallback: reload the page
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }
        },

        dismissUpdate() {
            this.showUpdateBanner = false;

            // Haptic feedback
            if (window.SmHaptic) {
                window.SmHaptic.light();
            }

            // Store dismiss time (will show again after 1 hour)
            sessionStorage.setItem('pwa_update_dismissed', Date.now().toString());
        },

        viewChangelog() {
            this.showChangelog = true;

            // Haptic feedback
            if (window.SmHaptic) {
                window.SmHaptic.light();
            }
        }
    };
}

// Override the default showUpdatePrompt from pwa.js
window.showUpdatePrompt = function(version) {
    window.dispatchEvent(new CustomEvent('pwa:update-available', {
        detail: { version: version }
    }));
};
</script>
