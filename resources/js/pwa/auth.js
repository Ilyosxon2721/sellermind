/**
 * SellerMind PWA - PIN & Biometric Authentication
 */

class SmAuth {
    constructor() {
        this.PIN_KEY = 'sm_pin_hash';
        this.TOKEN_KEY = 'sm_auth_token';
        this.BIOMETRIC_KEY = 'sm_biometric_enabled';
    }

    /**
     * Проверить доступность биометрии на устройстве
     */
    async isBiometricAvailable() {
        if (!window.PublicKeyCredential) return false;
        try {
            return await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        } catch {
            return false;
        }
    }

    /**
     * Хэширование PIN через SHA-256
     */
    async hashPin(pin) {
        const encoder = new TextEncoder();
        const data = encoder.encode(pin + 'sm_salt_2024');
        const hashBuffer = await crypto.subtle.digest('SHA-256', data);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }

    /**
     * Установить PIN-код
     */
    async setPin(pin) {
        const hash = await this.hashPin(pin);
        localStorage.setItem(this.PIN_KEY, hash);
        return true;
    }

    /**
     * Проверить PIN-код
     */
    async verifyPin(pin) {
        const stored = localStorage.getItem(this.PIN_KEY);
        if (!stored) return false;
        const hash = await this.hashPin(pin);
        return hash === stored;
    }

    /**
     * Проверить установлен ли PIN
     */
    hasPinSet() {
        return !!localStorage.getItem(this.PIN_KEY);
    }

    /**
     * Включить биометрию
     */
    enableBiometric(token) {
        localStorage.setItem(this.BIOMETRIC_KEY, 'true');
        localStorage.setItem(this.TOKEN_KEY, token);
    }

    /**
     * Проверить включена ли биометрия
     */
    isBiometricEnabled() {
        return localStorage.getItem(this.BIOMETRIC_KEY) === 'true';
    }

    /**
     * Аутентификация через биометрию (Web Authentication API)
     */
    async authenticateWithBiometric() {
        if (!this.isBiometricEnabled()) {
            throw new Error('Biometric not enabled');
        }

        try {
            const credential = await navigator.credentials.get({
                publicKey: {
                    challenge: new Uint8Array(32),
                    timeout: 60000,
                    userVerification: 'required',
                    rpId: window.location.hostname,
                }
            });

            if (credential) {
                return localStorage.getItem(this.TOKEN_KEY);
            }
        } catch (e) {
            throw e;
        }
    }

    /**
     * Очистить все auth данные
     */
    logout() {
        localStorage.removeItem(this.PIN_KEY);
        localStorage.removeItem(this.TOKEN_KEY);
        localStorage.removeItem(this.BIOMETRIC_KEY);
    }
}

window.SmAuth = new SmAuth();
