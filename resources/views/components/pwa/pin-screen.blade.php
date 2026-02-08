{{-- PIN Screen Component (PWA only) --}}
<div x-data="pinScreen()"
     x-show="showPinScreen"
     x-cloak
     class="pwa-only fixed inset-0 z-[1000]"
     style="background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);">

    <div class="flex flex-col items-center justify-center min-h-screen px-6 py-10">
        {{-- Logo --}}
        <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mb-6 backdrop-blur">
            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM12 17c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zM15.1 8H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/>
            </svg>
        </div>

        {{-- Title --}}
        <h1 class="text-2xl font-semibold text-white mb-2">SellerMind</h1>
        <p class="text-white/70 mb-10" x-text="statusText"></p>

        {{-- PIN Dots --}}
        <div class="flex gap-4 mb-12">
            <template x-for="i in 4" :key="i">
                <div class="w-4 h-4 rounded-full transition-all duration-150"
                     :class="{
                         'bg-white scale-110': pin.length >= i,
                         'bg-white/30': pin.length < i,
                         'sm-pin-error': error
                     }"></div>
            </template>
        </div>

        {{-- Keypad --}}
        <div class="grid grid-cols-3 gap-4 w-full max-w-[280px]">
            {{-- Digits 1-9 --}}
            <template x-for="num in [1,2,3,4,5,6,7,8,9]" :key="num">
                <button @click="addDigit(num)"
                        class="aspect-square rounded-full bg-white/15 text-white text-3xl font-light flex items-center justify-center backdrop-blur active:bg-white/30 active:scale-95 transition-all">
                    <span x-text="num"></span>
                </button>
            </template>

            {{-- Biometric button --}}
            <template x-if="biometricAvailable && !isSettingPin">
                <button @click="useBiometric()"
                        class="aspect-square rounded-full bg-white/10 text-white flex items-center justify-center active:bg-white/20 transition-all">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.81 4.47c-.08 0-.16-.02-.23-.06C15.66 3.42 14 3 12.01 3c-1.98 0-3.86.47-5.57 1.41-.24.13-.54.04-.68-.2-.13-.24-.04-.55.2-.68C7.82 2.52 9.86 2 12.01 2c2.13 0 3.99.47 6.03 1.52.25.13.34.43.21.67-.09.18-.26.28-.44.28zM3.5 9.72c-.1 0-.2-.03-.29-.09-.23-.16-.28-.47-.12-.7.99-1.4 2.25-2.5 3.75-3.27C9.98 4.04 14 4.03 17.15 5.65c1.5.77 2.76 1.86 3.75 3.25.16.22.11.54-.12.7-.23.16-.54.11-.7-.12-.9-1.26-2.04-2.25-3.39-2.94-2.87-1.47-6.54-1.47-9.4.01-1.36.7-2.5 1.7-3.4 2.96-.08.14-.23.21-.39.21zm6.25 12.07c-.13 0-.26-.05-.35-.15-.87-.87-1.34-1.43-2.01-2.64-.69-1.23-1.05-2.73-1.05-4.34 0-2.97 2.54-5.39 5.66-5.39s5.66 2.42 5.66 5.39c0 .28-.22.5-.5.5s-.5-.22-.5-.5c0-2.42-2.09-4.39-4.66-4.39-2.57 0-4.66 1.97-4.66 4.39 0 1.44.32 2.77.93 3.85.64 1.15 1.08 1.64 1.85 2.42.19.2.19.51 0 .71-.11.1-.24.15-.37.15zm7.17-1.85c-1.19 0-2.24-.3-3.1-.89-1.49-1.01-2.38-2.65-2.38-4.39 0-.28.22-.5.5-.5s.5.22.5.5c0 1.41.72 2.74 1.94 3.56.71.48 1.54.71 2.54.71.24 0 .64-.03 1.04-.1.27-.05.53.13.58.41.05.27-.13.53-.41.58-.57.11-1.07.12-1.21.12zM14.91 22c-.04 0-.09-.01-.13-.02-1.59-.44-2.63-1.03-3.72-2.1-1.4-1.39-2.17-3.24-2.17-5.22 0-1.62 1.38-2.94 3.08-2.94 1.7 0 3.08 1.32 3.08 2.94 0 1.07.93 1.94 2.08 1.94s2.08-.87 2.08-1.94c0-3.77-3.25-6.83-7.25-6.83-2.84 0-5.44 1.58-6.61 4.03-.39.81-.59 1.76-.59 2.8 0 .78.07 2.01.67 3.61.1.26-.03.55-.29.64-.26.1-.55-.04-.64-.29-.49-1.31-.73-2.61-.73-3.96 0-1.2.23-2.29.68-3.24 1.33-2.79 4.28-4.6 7.51-4.6 4.55 0 8.25 3.51 8.25 7.83 0 1.62-1.38 2.94-3.08 2.94s-3.08-1.32-3.08-2.94c0-1.07-.93-1.94-2.08-1.94s-2.08.87-2.08 1.94c0 1.71.66 3.31 1.87 4.51.95.94 1.86 1.46 3.27 1.85.27.07.42.35.35.61-.05.23-.26.38-.48.38z"/>
                    </svg>
                </button>
            </template>
            <template x-if="!biometricAvailable || isSettingPin">
                <div class="aspect-square"></div>
            </template>

            {{-- Zero --}}
            <button @click="addDigit(0)"
                    class="aspect-square rounded-full bg-white/15 text-white text-3xl font-light flex items-center justify-center backdrop-blur active:bg-white/30 active:scale-95 transition-all">
                0
            </button>

            {{-- Delete --}}
            <button @click="removeDigit()"
                    class="aspect-square rounded-full bg-white/10 text-white flex items-center justify-center active:bg-white/20 transition-all">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l7-7h11a1 1 0 011 1v12a1 1 0 01-1 1H10l-7-7z"/>
                </svg>
            </button>
        </div>

        {{-- Footer --}}
        <div class="mt-10">
            <a href="#" @click.prevent="forgotPin()" class="text-white/70 text-sm" x-show="!isSettingPin">
                {{ __('Забыли PIN? Войти иначе') }}
            </a>
        </div>
    </div>
</div>

<style>
@keyframes sm-pin-shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-8px); }
    40%, 80% { transform: translateX(8px); }
}
.sm-pin-error {
    background-color: #ef4444 !important;
    animation: sm-pin-shake 0.5s ease;
}
</style>

<script>
function pinScreen() {
    return {
        showPinScreen: false,
        pin: '',
        error: false,
        isSettingPin: false,
        biometricAvailable: false,
        confirmPin: '',

        get statusText() {
            if (this.isSettingPin) {
                return this.confirmPin ? 'Повторите PIN-код' : 'Создайте PIN-код';
            }
            return 'Введите PIN-код';
        },

        async init() {
            // Только в PWA режиме
            const isPWA = window.matchMedia('(display-mode: standalone)').matches ||
                          window.navigator.standalone;

            if (!isPWA) return;
            if (!window.SmAuth) return;

            // Проверить биометрию
            this.biometricAvailable = await window.SmAuth.isBiometricAvailable() &&
                                      window.SmAuth.isBiometricEnabled();

            // Показать PIN-экран если PIN установлен
            if (window.SmAuth.hasPinSet()) {
                this.showPinScreen = true;

                // Попробовать биометрию первой
                if (this.biometricAvailable) {
                    setTimeout(() => this.useBiometric(), 500);
                }
            }
        },

        addDigit(num) {
            if (this.pin.length >= 4) return;
            if (window.SmHaptic) window.SmHaptic.light();
            this.pin += num;

            if (this.pin.length === 4) {
                setTimeout(() => this.verify(), 200);
            }
        },

        removeDigit() {
            if (this.pin.length > 0) {
                if (window.SmHaptic) window.SmHaptic.light();
                this.pin = this.pin.slice(0, -1);
            }
        },

        async verify() {
            if (this.isSettingPin) {
                if (!this.confirmPin) {
                    // Первый ввод — запомнить и попросить подтвердить
                    this.confirmPin = this.pin;
                    this.pin = '';
                    return;
                }

                if (this.pin === this.confirmPin) {
                    await window.SmAuth.setPin(this.pin);
                    this.onSuccess();
                } else {
                    this.onError();
                    this.confirmPin = '';
                }
            } else {
                const valid = await window.SmAuth.verifyPin(this.pin);
                if (valid) {
                    this.onSuccess();
                } else {
                    this.onError();
                }
            }
        },

        async useBiometric() {
            try {
                await window.SmAuth.authenticateWithBiometric();
                this.onSuccess();
            } catch (e) {
                // Биометрия не удалась — пользователь введёт PIN вручную
            }
        },

        onError() {
            this.error = true;
            if (window.SmHaptic) window.SmHaptic.error();
            setTimeout(() => {
                this.error = false;
                this.pin = '';
            }, 500);
        },

        onSuccess() {
            if (window.SmHaptic) window.SmHaptic.success();
            this.showPinScreen = false;
        },

        forgotPin() {
            window.SmAuth.logout();
            window.location.href = '/login';
        }
    };
}
</script>
