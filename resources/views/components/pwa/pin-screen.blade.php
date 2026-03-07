{{-- PIN Screen Component (PWA only) --}}
{{-- НЕ используем класс pwa-only — он имеет display:block !important и ломает x-show --}}
<div x-data="pinScreen()"
     x-show="showPinScreen"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="sm-pin-screen"
     @touchmove.prevent>

    <div class="sm-pin-container">
        {{-- Logo --}}
        <div class="sm-pin-logo">
            <svg class="w-10 h-10" style="color: #2563eb;" fill="currentColor" viewBox="0 0 24 24">
                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM12 17c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zM15.1 8H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/>
            </svg>
        </div>

        {{-- Title --}}
        <h1 class="sm-pin-title">SellerMind</h1>
        <p class="sm-pin-subtitle" x-text="statusText"></p>

        {{-- PIN Dots --}}
        <div class="sm-pin-dots">
            <template x-for="i in 4" :key="i">
                <div class="sm-pin-dot"
                     :class="{
                         'filled': pin.length >= i,
                         'sm-pin-error': error
                     }"></div>
            </template>
        </div>

        {{-- Keypad --}}
        <div class="sm-pin-keypad">
            {{-- Digits 1-9 --}}
            <template x-for="num in [1,2,3,4,5,6,7,8,9]" :key="num">
                <button @click="addDigit(num)" class="sm-pin-key" type="button">
                    <span x-text="num"></span>
                </button>
            </template>

            {{-- Biometric button --}}
            <template x-if="biometricAvailable && !isSettingPin">
                <button @click="useBiometric()" class="sm-pin-key sm-pin-key-action" type="button">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.81 4.47c-.08 0-.16-.02-.23-.06C15.66 3.42 14 3 12.01 3c-1.98 0-3.86.47-5.57 1.41-.24.13-.54.04-.68-.2-.13-.24-.04-.55.2-.68C7.82 2.52 9.86 2 12.01 2c2.13 0 3.99.47 6.03 1.52.25.13.34.43.21.67-.09.18-.26.28-.44.28zM3.5 9.72c-.1 0-.2-.03-.29-.09-.23-.16-.28-.47-.12-.7.99-1.4 2.25-2.5 3.75-3.27C9.98 4.04 14 4.03 17.15 5.65c1.5.77 2.76 1.86 3.75 3.25.16.22.11.54-.12.7-.23.16-.54.11-.7-.12-.9-1.26-2.04-2.25-3.39-2.94-2.87-1.47-6.54-1.47-9.4.01-1.36.7-2.5 1.7-3.4 2.96-.08.14-.23.21-.39.21zm6.25 12.07c-.13 0-.26-.05-.35-.15-.87-.87-1.34-1.43-2.01-2.64-.69-1.23-1.05-2.73-1.05-4.34 0-2.97 2.54-5.39 5.66-5.39s5.66 2.42 5.66 5.39c0 .28-.22.5-.5.5s-.5-.22-.5-.5c0-2.42-2.09-4.39-4.66-4.39-2.57 0-4.66 1.97-4.66 4.39 0 1.44.32 2.77.93 3.85.64 1.15 1.08 1.64 1.85 2.42.19.2.19.51 0 .71-.11.1-.24.15-.37.15zm7.17-1.85c-1.19 0-2.24-.3-3.1-.89-1.49-1.01-2.38-2.65-2.38-4.39 0-.28.22-.5.5-.5s.5.22.5.5c0 1.41.72 2.74 1.94 3.56.71.48 1.54.71 2.54.71.24 0 .64-.03 1.04-.1.27-.05.53.13.58.41.05.27-.13.53-.41.58-.57.11-1.07.12-1.21.12zM14.91 22c-.04 0-.09-.01-.13-.02-1.59-.44-2.63-1.03-3.72-2.1-1.4-1.39-2.17-3.24-2.17-5.22 0-1.62 1.38-2.94 3.08-2.94 1.7 0 3.08 1.32 3.08 2.94 0 1.07.93 1.94 2.08 1.94s2.08-.87 2.08-1.94c0-3.77-3.25-6.83-7.25-6.83-2.84 0-5.44 1.58-6.61 4.03-.39.81-.59 1.76-.59 2.8 0 .78.07 2.01.67 3.61.1.26-.03.55-.29.64-.26.1-.55-.04-.64-.29-.49-1.31-.73-2.61-.73-3.96 0-1.2.23-2.29.68-3.24 1.33-2.79 4.28-4.6 7.51-4.6 4.55 0 8.25 3.51 8.25 7.83 0 1.62-1.38 2.94-3.08 2.94s-3.08-1.32-3.08-2.94c0-1.07-.93-1.94-2.08-1.94s-2.08.87-2.08 1.94c0 1.71.66 3.31 1.87 4.51.95.94 1.86 1.46 3.27 1.85.27.07.42.35.35.61-.05.23-.26.38-.48.38z"/>
                    </svg>
                </button>
            </template>
            <template x-if="!biometricAvailable || isSettingPin">
                <div class="sm-pin-key-placeholder"></div>
            </template>

            {{-- Zero --}}
            <button @click="addDigit(0)" class="sm-pin-key" type="button">
                <span>0</span>
            </button>

            {{-- Delete --}}
            <button @click="removeDigit()" class="sm-pin-key sm-pin-key-action" type="button">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l7-7h11a1 1 0 011 1v12a1 1 0 01-1 1H10l-7-7z"/>
                </svg>
            </button>
        </div>

        {{-- Footer --}}
        <div class="sm-pin-footer" x-show="!isSettingPin">
            <button @click="forgotPin()" class="sm-pin-forgot" type="button">
                Забыли PIN? Войти заново
            </button>
        </div>
    </div>
</div>

<style>
/* === PIN Screen — белый фон, тёмный текст === */
.sm-pin-screen {
    position: fixed;
    inset: 0;
    z-index: 10000;
    background: #ffffff;
    overflow: hidden;
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
}

.sm-pin-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    height: 100dvh;
    padding: 40px 24px env(safe-area-inset-bottom, 20px);
    box-sizing: border-box;
    overflow: hidden;
}

.sm-pin-logo {
    width: 72px;
    height: 72px;
    background: #EBF0FF;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}

.sm-pin-title {
    font-size: 24px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 6px;
}

.sm-pin-subtitle {
    font-size: 15px;
    color: #888;
    margin: 0 0 36px;
}

.sm-pin-dots {
    display: flex;
    gap: 18px;
    margin-bottom: 40px;
}

.sm-pin-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #e5e7eb;
    border: 2px solid #d1d5db;
    transition: all 0.15s ease;
}

.sm-pin-dot.filled {
    background: #2563eb;
    border-color: #2563eb;
    transform: scale(1.15);
}

/* Keypad */
.sm-pin-keypad {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    width: 100%;
    max-width: 320px;
}

.sm-pin-key {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    color: #1a1a1a;
    font-size: 32px;
    font-weight: 300;
    display: flex;
    align-items: center;
    justify-content: center;
    justify-self: center;
    cursor: pointer;
    transition: all 0.1s ease;
    -webkit-tap-highlight-color: transparent;
    outline: none;
}

.sm-pin-key:active {
    background: #e5e7eb;
    transform: scale(0.93);
}

.sm-pin-key-action {
    background: transparent;
    border-color: transparent;
    color: #6b7280;
}

.sm-pin-key-action:active {
    background: #f3f4f6;
}

.sm-pin-key-placeholder {
    width: 80px;
    height: 80px;
    justify-self: center;
}

/* Footer */
.sm-pin-footer {
    margin-top: 28px;
}

.sm-pin-forgot {
    background: none;
    border: none;
    color: #2563eb;
    font-size: 14px;
    cursor: pointer;
    padding: 8px 16px;
    -webkit-tap-highlight-color: transparent;
}

.sm-pin-forgot:active {
    opacity: 0.6;
}

/* Shake animation on error */
@keyframes sm-pin-shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-10px); }
    40%, 80% { transform: translateX(10px); }
}
.sm-pin-error {
    background-color: #ef4444 !important;
    border-color: #ef4444 !important;
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
            // Только в PWA standalone режиме
            const isPWA = window.matchMedia('(display-mode: standalone)').matches ||
                          window.navigator.standalone === true;

            if (!isPWA) return;
            if (!window.SmAuth) return;

            // Если PIN не установлен — НЕ показывать экран
            // PIN устанавливается пользователем добровольно через настройки
            if (!window.SmAuth.hasPinSet()) return;

            // Проверить биометрию
            try {
                this.biometricAvailable = await window.SmAuth.isBiometricAvailable() &&
                                          window.SmAuth.isBiometricEnabled();
            } catch (e) {
                this.biometricAvailable = false;
            }

            // Показать PIN-экран
            this.showPinScreen = true;
            document.body.style.overflow = 'hidden';

            // Попробовать биометрию первой
            if (this.biometricAvailable) {
                setTimeout(() => this.useBiometric(), 500);
            }
        },

        addDigit(num) {
            if (this.pin.length >= 4) return;
            if (window.SmHaptic) window.SmHaptic.light();
            this.pin += String(num);

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
            document.body.style.overflow = '';
        },

        forgotPin() {
            // Сбросить PIN и перенаправить на логин
            if (window.SmAuth) {
                window.SmAuth.logout();
            }
            window.location.href = '/login';
        }
    };
}
</script>
