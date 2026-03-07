{{-- PIN Screen - Shows in PWA mode when PIN is set --}}
{{-- Only displays if user has previously set up a PIN code --}}

<div id="pin-screen"
     x-data="pinScreen()"
     x-show="showPinScreen"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="sm-pin-screen">

    <div class="sm-pin-container">
        {{-- Logo --}}
        <div class="sm-pin-logo">
            <div class="sm-pin-logo-icon">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>

        {{-- Title --}}
        <h1 class="sm-pin-title" x-text="isSettingPin ? (confirmPin ? '{{ __('pwa.pin.confirm_title') }}' : '{{ __('pwa.pin.setup_title') }}') : '{{ __('pwa.pin.enter_title') }}'">
            {{ __('pwa.pin.enter_title') }}
        </h1>
        <p class="sm-pin-subtitle" x-text="isSettingPin ? (confirmPin ? '{{ __('pwa.pin.confirm_subtitle') }}' : '{{ __('pwa.pin.setup_subtitle') }}') : '{{ __('pwa.pin.enter_subtitle') }}'">
            {{ __('pwa.pin.enter_subtitle') }}
        </p>

        {{-- PIN Dots --}}
        <div class="sm-pin-dots">
            <template x-for="i in 4" :key="i">
                <div class="sm-pin-dot" :class="{ 'filled': pin.length >= i, 'error': error }"></div>
            </template>
        </div>

        {{-- Error Message --}}
        <div x-show="errorMessage" x-cloak class="sm-pin-error" x-text="errorMessage"></div>

        {{-- Keypad --}}
        <div class="sm-pin-keypad">
            <template x-for="num in [1, 2, 3, 4, 5, 6, 7, 8, 9]" :key="num">
                <button type="button"
                        class="sm-pin-key"
                        @click="addDigit(num)"
                        :disabled="pin.length >= 4">
                    <span x-text="num"></span>
                </button>
            </template>

            {{-- Biometric Button (Face ID / Touch ID) --}}
            <button type="button"
                    class="sm-pin-key sm-pin-key-action"
                    x-show="biometricAvailable && !isSettingPin"
                    @click="useBiometric()">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </button>

            {{-- Empty placeholder when biometric not available --}}
            <div x-show="!biometricAvailable || isSettingPin" class="sm-pin-key-placeholder"></div>

            {{-- Zero --}}
            <button type="button"
                    class="sm-pin-key"
                    @click="addDigit(0)"
                    :disabled="pin.length >= 4">
                <span>0</span>
            </button>

            {{-- Backspace --}}
            <button type="button"
                    class="sm-pin-key sm-pin-key-action"
                    @click="removeDigit()"
                    :disabled="pin.length === 0">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"/>
                </svg>
            </button>
        </div>

        {{-- Forgot PIN Link --}}
        <button type="button"
                class="sm-pin-forgot"
                x-show="!isSettingPin"
                @click="forgotPin()">
            {{ __('pwa.pin.forgot') }}
        </button>

        {{-- Cancel Setup --}}
        <button type="button"
                class="sm-pin-forgot"
                x-show="isSettingPin"
                @click="cancelSetup()">
            {{ __('pwa.pin.cancel') }}
        </button>
    </div>
</div>

{{-- PIN Setup Prompt Modal --}}
<div x-data="pinSetupPrompt()"
     x-show="showPrompt"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     class="sm-pin-modal-overlay">
    <div class="sm-pin-modal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">
        <div class="sm-pin-modal-icon">
            <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h3 class="sm-pin-modal-title">{{ __('pwa.pin.setup_prompt_title') }}</h3>
        <p class="sm-pin-modal-text">{{ __('pwa.pin.setup_prompt_text') }}</p>
        <div class="sm-pin-modal-actions">
            <button type="button" class="sm-pin-modal-btn sm-pin-modal-btn-secondary" @click="dismiss()">
                {{ __('pwa.pin.later') }}
            </button>
            <button type="button" class="sm-pin-modal-btn sm-pin-modal-btn-primary" @click="startSetup()">
                {{ __('pwa.pin.setup_now') }}
            </button>
        </div>
    </div>
</div>

<style>
/* ============================================
   PIN Screen - Full Screen Overlay
   ============================================ */
.sm-pin-screen {
    position: fixed;
    inset: 0;
    background: linear-gradient(180deg, #4F46E5 0%, #7C3AED 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 9999;

    /* Prevent scrolling */
    overflow: hidden;
    touch-action: none;
    -webkit-overflow-scrolling: none;
    overscroll-behavior: none;

    /* Safe areas */
    padding: env(safe-area-inset-top, 20px) env(safe-area-inset-right, 20px) env(safe-area-inset-bottom, 20px) env(safe-area-inset-left, 20px);
}

.sm-pin-container {
    width: 100%;
    max-width: 320px;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
}

/* Logo */
.sm-pin-logo {
    margin-bottom: 24px;
}

.sm-pin-logo-icon {
    width: 72px;
    height: 72px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

/* Title & Subtitle */
.sm-pin-title {
    font-size: 26px;
    font-weight: 700;
    color: #FFFFFF;
    margin: 0 0 8px 0;
    text-align: center;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

.sm-pin-subtitle {
    font-size: 15px;
    color: rgba(255, 255, 255, 0.9);
    margin: 0 0 32px 0;
    text-align: center;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* PIN Dots */
.sm-pin-dots {
    display: flex;
    gap: 18px;
    margin-bottom: 12px;
}

.sm-pin-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    border: 2px solid rgba(255, 255, 255, 0.5);
    transition: all 0.15s ease;
}

.sm-pin-dot.filled {
    background: #FFFFFF;
    border-color: #FFFFFF;
    transform: scale(1.1);
    box-shadow: 0 0 12px rgba(255, 255, 255, 0.5);
}

.sm-pin-dot.error {
    background: #EF4444;
    border-color: #EF4444;
    animation: shake 0.4s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-8px); }
    40% { transform: translateX(8px); }
    60% { transform: translateX(-6px); }
    80% { transform: translateX(6px); }
}

/* Error Message */
.sm-pin-error {
    color: #FCA5A5;
    font-size: 14px;
    margin-bottom: 16px;
    text-align: center;
    min-height: 20px;
}

/* Keypad */
.sm-pin-keypad {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    width: 100%;
    max-width: 280px;
    margin-bottom: 24px;
}

.sm-pin-key {
    width: 80px;
    height: 80px;
    border: none;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    font-size: 30px;
    font-weight: 400;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.1s ease;
    -webkit-tap-highlight-color: transparent;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    justify-self: center;
}

.sm-pin-key:active:not(:disabled) {
    background: rgba(255, 255, 255, 0.35);
    transform: scale(0.95);
}

.sm-pin-key:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.sm-pin-key-action {
    background: rgba(255, 255, 255, 0.1);
}

.sm-pin-key-action:active:not(:disabled) {
    background: rgba(255, 255, 255, 0.25);
}

.sm-pin-key-placeholder {
    width: 80px;
    height: 80px;
    justify-self: center;
}

/* Forgot PIN Link */
.sm-pin-forgot {
    color: rgba(255, 255, 255, 0.9);
    font-size: 15px;
    font-weight: 500;
    background: none;
    border: none;
    padding: 12px 24px;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    border-radius: 8px;
    transition: all 0.15s ease;
}

.sm-pin-forgot:active {
    background: rgba(255, 255, 255, 0.1);
}

/* ============================================
   PIN Setup Prompt Modal
   ============================================ */
.sm-pin-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9998;
    padding: 20px;
}

.sm-pin-modal {
    background: white;
    border-radius: 20px;
    padding: 28px 24px;
    max-width: 320px;
    width: 100%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.sm-pin-modal-icon {
    width: 64px;
    height: 64px;
    background: #EEF2FF;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}

.sm-pin-modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 8px 0;
}

.sm-pin-modal-text {
    font-size: 14px;
    color: #6B7280;
    margin: 0 0 24px 0;
    line-height: 1.5;
}

.sm-pin-modal-actions {
    display: flex;
    gap: 12px;
}

.sm-pin-modal-btn {
    flex: 1;
    padding: 14px 16px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    border: none;
    -webkit-tap-highlight-color: transparent;
}

.sm-pin-modal-btn-secondary {
    background: #F3F4F6;
    color: #374151;
}

.sm-pin-modal-btn-secondary:active {
    background: #E5E7EB;
}

.sm-pin-modal-btn-primary {
    background: #4F46E5;
    color: white;
}

.sm-pin-modal-btn-primary:active {
    background: #4338CA;
}

/* ============================================
   Larger buttons for tablets
   ============================================ */
@media (min-width: 500px) {
    .sm-pin-key {
        width: 90px;
        height: 90px;
        font-size: 34px;
    }

    .sm-pin-key-placeholder {
        width: 90px;
        height: 90px;
    }

    .sm-pin-keypad {
        gap: 16px;
        max-width: 320px;
    }
}
</style>

<script>
function pinScreen() {
    return {
        showPinScreen: false,
        pin: '',
        confirmPin: '',
        error: false,
        errorMessage: '',
        isSettingPin: false,
        biometricAvailable: false,
        attempts: 0,
        maxAttempts: 5,

        async init() {
            // Only run in PWA mode
            const isPWA = window.matchMedia('(display-mode: standalone)').matches ||
                         window.navigator.standalone === true;

            if (!isPWA) return;

            // Check biometric availability
            this.biometricAvailable = await this.checkBiometric();

            // Check if PIN is set
            const hasPinSet = localStorage.getItem('sm_pin_hash');

            if (hasPinSet) {
                // Show PIN screen
                this.showPinScreen = true;
                this.lockBody(true);

                // Try biometric first if enabled
                if (this.biometricAvailable && localStorage.getItem('sm_biometric_enabled') === 'true') {
                    setTimeout(() => this.useBiometric(), 600);
                }
            }

            // Listen for setup trigger
            window.addEventListener('sm-pin-setup', () => {
                this.startSetup();
            });

            // Watch for changes
            this.$watch('showPinScreen', (show) => {
                this.lockBody(show);
            });
        },

        lockBody(lock) {
            if (lock) {
                document.body.style.overflow = 'hidden';
                document.body.style.position = 'fixed';
                document.body.style.width = '100%';
                document.body.style.height = '100%';
                document.body.style.top = '0';
            } else {
                document.body.style.overflow = '';
                document.body.style.position = '';
                document.body.style.width = '';
                document.body.style.height = '';
                document.body.style.top = '';
            }
        },

        addDigit(num) {
            if (this.pin.length >= 4) return;

            this.pin += num.toString();
            this.error = false;
            this.errorMessage = '';
            this.haptic(10);

            if (this.pin.length === 4) {
                setTimeout(() => {
                    if (this.isSettingPin) {
                        this.handleSetup();
                    } else {
                        this.verifyPin();
                    }
                }, 100);
            }
        },

        removeDigit() {
            if (this.pin.length > 0) {
                this.pin = this.pin.slice(0, -1);
                this.haptic(10);
            }
        },

        async verifyPin() {
            const storedHash = localStorage.getItem('sm_pin_hash');

            if (!storedHash) {
                // PIN not set - shouldn't happen, but handle it
                console.error('PIN not set but screen shown');
                this.showPinScreen = false;
                return;
            }

            const inputHash = await this.hashPin(this.pin);

            if (inputHash === storedHash) {
                this.success();
            } else {
                this.attempts++;
                this.showError('{{ __('pwa.pin.wrong_pin') }}');

                if (this.attempts >= this.maxAttempts) {
                    this.errorMessage = '{{ __('pwa.pin.too_many_attempts') }}';
                    setTimeout(() => this.forgotPin(), 2000);
                }
            }
        },

        async handleSetup() {
            if (!this.confirmPin) {
                // First entry - save and ask for confirmation
                this.confirmPin = this.pin;
                this.pin = '';
                return;
            }

            // Second entry - verify match
            if (this.pin === this.confirmPin) {
                const hash = await this.hashPin(this.pin);
                localStorage.setItem('sm_pin_hash', hash);

                // Ask about biometric if available
                if (this.biometricAvailable) {
                    const enableBiometric = confirm('{{ __('pwa.pin.enable_biometric') }}');
                    if (enableBiometric) {
                        localStorage.setItem('sm_biometric_enabled', 'true');
                    }
                }

                this.success();
                // Dispatch event for other components
                window.dispatchEvent(new CustomEvent('sm-pin-set'));
            } else {
                this.showError('{{ __('pwa.pin.mismatch') }}');
                this.confirmPin = '';
            }
        },

        showError(message = '') {
            this.error = true;
            this.errorMessage = message;
            this.haptic([50, 100, 50]);

            setTimeout(() => {
                this.error = false;
                this.pin = '';
            }, 400);
        },

        success() {
            this.haptic([10, 50, 10]);
            this.showPinScreen = false;
            this.isSettingPin = false;
            this.pin = '';
            this.confirmPin = '';
            this.attempts = 0;
            this.errorMessage = '';
        },

        forgotPin() {
            const confirmed = confirm('{{ __('pwa.pin.forgot_confirm') }}');
            if (confirmed) {
                // Clear PIN data
                localStorage.removeItem('sm_pin_hash');
                localStorage.removeItem('sm_biometric_enabled');

                // Redirect to login
                window.location.href = '/login';
            }
        },

        cancelSetup() {
            this.isSettingPin = false;
            this.showPinScreen = false;
            this.pin = '';
            this.confirmPin = '';
        },

        startSetup() {
            this.isSettingPin = true;
            this.showPinScreen = true;
            this.pin = '';
            this.confirmPin = '';
            this.error = false;
            this.errorMessage = '';
        },

        async hashPin(pin) {
            const encoder = new TextEncoder();
            const data = encoder.encode(pin + 'sellermind_salt_2024');
            const hashBuffer = await crypto.subtle.digest('SHA-256', data);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        },

        async checkBiometric() {
            if (!window.PublicKeyCredential) return false;
            try {
                return await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            } catch {
                return false;
            }
        },

        async useBiometric() {
            try {
                // Use Web Authentication API for biometric
                const credential = await navigator.credentials.get({
                    publicKey: {
                        challenge: new Uint8Array(32),
                        timeout: 60000,
                        userVerification: 'required',
                        rpId: window.location.hostname,
                        allowCredentials: []
                    }
                });

                if (credential) {
                    this.success();
                }
            } catch (e) {
                console.log('Biometric cancelled or failed:', e.message);
                // User cancelled or biometric failed - they can use PIN
            }
        },

        haptic(pattern = 10) {
            if (navigator.vibrate) {
                navigator.vibrate(pattern);
            }
        }
    };
}

function pinSetupPrompt() {
    return {
        showPrompt: false,

        init() {
            // Only check in PWA mode after successful login
            const isPWA = window.matchMedia('(display-mode: standalone)').matches ||
                         window.navigator.standalone === true;

            if (!isPWA) return;

            // Listen for login success to show prompt
            window.addEventListener('sm-login-success', () => {
                this.checkAndShowPrompt();
            });

            // Check on page load if user is authenticated and PIN not set
            setTimeout(() => {
                if (this.shouldShowPrompt()) {
                    this.showPrompt = true;
                }
            }, 2000);
        },

        shouldShowPrompt() {
            // Don't show if PIN already set
            if (localStorage.getItem('sm_pin_hash')) return false;

            // Don't show if user dismissed recently (within 7 days)
            const dismissed = localStorage.getItem('sm_pin_prompt_dismissed');
            if (dismissed) {
                const dismissedTime = parseInt(dismissed);
                const sevenDays = 7 * 24 * 60 * 60 * 1000;
                if (Date.now() - dismissedTime < sevenDays) return false;
            }

            // Check if user is authenticated
            const isAuth = typeof Alpine !== 'undefined' &&
                          Alpine.store('auth') &&
                          Alpine.store('auth').isAuthenticated;

            return isAuth;
        },

        checkAndShowPrompt() {
            if (this.shouldShowPrompt()) {
                setTimeout(() => {
                    this.showPrompt = true;
                }, 1000);
            }
        },

        dismiss() {
            this.showPrompt = false;
            localStorage.setItem('sm_pin_prompt_dismissed', Date.now().toString());
        },

        startSetup() {
            this.showPrompt = false;
            // Trigger PIN setup
            window.dispatchEvent(new CustomEvent('sm-pin-setup'));
        }
    };
}
</script>
