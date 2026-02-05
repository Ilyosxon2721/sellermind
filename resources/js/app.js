import './bootstrap';
import './polling'; // HTTP polling для real-time обновлений
import './haptic'; // Haptic feedback system
import './loading'; // Global loading indicator
import './pull-to-refresh'; // Pull-to-refresh gesture
import './page-transitions'; // Native page transitions
import './action-sheet'; // Native action sheets
import './toast'; // Toast notifications
import './empty-state'; // Empty states
import './share'; // Web Share API
import './swipe-actions'; // Swipe actions
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import api, { auth, companies, products, dialogs, chat, images, tasks } from './services/api';

// Register Alpine plugins
Alpine.plugin(persist);

// Helper: Safe persist with error handling
function safePersist(defaultValue, key) {
    try {
        return Alpine.$persist(defaultValue).as(key);
    } catch (e) {
        console.error(`Failed to initialize persist for ${key}:`, e);
        // Clean up corrupted value
        localStorage.removeItem('_x_' + key);
        localStorage.removeItem(key);
        return defaultValue;
    }
}

// Clean up any corrupted localStorage on load
try {
    const keysToCheck = ['auth_user', 'auth_token', 'current_company', 'nav_position', 'sidebar_collapsed'];
    keysToCheck.forEach(key => {
        const prefixedKey = '_x_' + key;
        const value = localStorage.getItem(prefixedKey);
        if (value) {
            try {
                JSON.parse(value);
            } catch (e) {
                console.warn(`Removing corrupted localStorage key: ${prefixedKey}`);
                localStorage.removeItem(prefixedKey);
                localStorage.removeItem(key);
            }
        }
    });
} catch (e) {
    console.error('localStorage cleanup failed:', e);
}

// Global API access - include raw api for get/post/delete methods
window.api = { ...api, auth, companies, products, dialogs, chat, images, tasks, get: api.get, post: api.post, delete: api.delete };

// Auth Store
Alpine.store('auth', {
    user: safePersist(null, 'auth_user'),
    token: safePersist(null, 'auth_token'),
    currentCompany: safePersist(null, 'current_company'),
    companies: safePersist([], 'auth_companies'),
    showCompanyPrompt: false,

    get isAuthenticated() {
        return !!this.token;
    },

    get hasCompanies() {
        return this.companies.length > 0;
    },

    async login(email, password) {
        const result = await auth.login(email, password);
        this.user = result.user;
        this.token = result.token;

        // Ensure token is saved to localStorage immediately
        localStorage.setItem('_x_auth_token', JSON.stringify(result.token));
        localStorage.setItem('_x_auth_user', JSON.stringify(result.user));

        // Load companies and auto-select first one
        await this.loadCompanies();
        return result;
    },

    async register(data) {
        const result = await auth.register(data);
        this.user = result.user;
        this.token = result.token;

        // Ensure token is saved to localStorage immediately
        localStorage.setItem('_x_auth_token', JSON.stringify(result.token));
        localStorage.setItem('_x_auth_user', JSON.stringify(result.user));

        // Load companies and auto-select first one
        await this.loadCompanies();
        return result;
    },

    async loadCompanies(retryCount = 0) {
        const maxRetries = 3;
        const retryDelay = 500; // ms

        try {
            console.log('Loading companies...', retryCount > 0 ? `(retry ${retryCount})` : '');
            this.companies = await companies.list();
            console.log('Companies loaded:', this.companies);

            // Auto-select company: prefer user's company_id, then persisted, then first
            if (this.companies.length > 0) {
                const currentExists = this.currentCompany &&
                    this.companies.some(c => c.id === this.currentCompany.id);
                if (!currentExists) {
                    // Try to find user's primary company first
                    const userCompanyId = this.user?.company_id;
                    console.log('User company_id:', userCompanyId);
                    const userCompany = userCompanyId
                        ? this.companies.find(c => c.id === userCompanyId)
                        : null;
                    this.currentCompany = userCompany || this.companies[0];
                    console.log('Selected company:', this.currentCompany);

                    // Ensure persist saves immediately
                    localStorage.setItem('_x_current_company', JSON.stringify(this.currentCompany));
                }
                this.showCompanyPrompt = false;
            } else {
                // No companies found - check if user has company_id but no access
                console.log('No companies found for user');
                this.showCompanyPrompt = true;
                this.currentCompany = null;
            }
            // Always load dialogs for current company
            if (this.currentCompany) {
                await Alpine.store('chat').loadDialogs(this.currentCompany.id);
            }
        } catch (e) {
            console.error('Failed to load companies:', e);

            // Retry на 401 — сессия могла не успеть синхронизироваться
            if (e.response?.status === 401 && retryCount < maxRetries) {
                console.log(`Retrying loadCompanies in ${retryDelay}ms...`);
                await new Promise(r => setTimeout(r, retryDelay * (retryCount + 1)));
                return this.loadCompanies(retryCount + 1);
            }

            // Не выбрасываем ошибку — позволяем редиректу на dashboard произойти
            // Companies загрузятся после page reload
        }
    },

    async ensureCompaniesLoaded() {
        if (!this.hasCompanies) {
            await this.loadCompanies();
        }
        // Wait for Alpine persist to complete
        await new Promise(resolve => setTimeout(resolve, 50));
        return this.companies;
    },

    async logout() {
        await auth.logout();
        this.user = null;
        this.token = null;
        this.currentCompany = null;
        this.companies = [];
    },

    setCompany(company) {
        this.currentCompany = company;
        // Load dialogs for new company
        if (company) {
            Alpine.store('chat').loadDialogs(company.id);
        }
    },
});

// Chat Store
Alpine.store('chat', {
    dialogs: [],
    currentDialog: null,
    messages: [],
    loading: false,

    async loadDialogs(companyId) {
        try {
            const result = await dialogs.list(companyId);
            this.dialogs = result.dialogs || [];
        } catch (e) {
            console.error('Failed to load dialogs:', e);
            this.dialogs = [];
        }
    },

    async loadDialog(dialogId) {
        this.loading = true;
        const dialog = await dialogs.get(dialogId);
        this.currentDialog = dialog;
        this.messages = dialog.messages || [];
        this.loading = false;
    },

    async sendMessage(content, options = {}) {
        this.loading = true;

        const data = {
            message: content,
            mode: options.mode || 'chat',
            model: options.model || 'fast',
        };

        // Add image_model for photos mode
        if (options.image_model) {
            data.image_model = options.image_model;
        }

        // Add is_private for private chat mode
        if (options.is_private) {
            data.is_private = true;
        }

        if (this.currentDialog) {
            data.dialog_id = this.currentDialog.id;
        } else {
            const store = Alpine.store('auth');
            data.company_id = store.currentCompany?.id;
        }

        if (options.images?.length) {
            data.images = options.images;
        }

        // Add user message immediately for better UX
        const tempUserMsg = {
            id: 'temp-' + Date.now(),
            sender: 'user',
            content: content,
            created_at: new Date().toISOString()
        };
        this.messages.push(tempUserMsg);

        try {
            const result = await chat.send(data);

            // If this was a new dialog, add it to the list
            if (!this.currentDialog) {
                this.currentDialog = result.dialog;
                // Add new dialog to the beginning of the list
                this.dialogs.unshift(result.dialog);
            } else {
                // Update existing dialog in the list (for title update)
                const dialogIndex = this.dialogs.findIndex(d => d.id === result.dialog.id);
                if (dialogIndex !== -1) {
                    this.dialogs[dialogIndex] = result.dialog;
                }
            }

            // Replace temp message with real one
            const tempIndex = this.messages.findIndex(m => m.id === tempUserMsg.id);
            if (tempIndex !== -1) {
                this.messages[tempIndex] = result.user_message;
            }

            this.messages.push(result.assistant_message);
            this.loading = false;

            return result;
        } catch (error) {
            // Remove temp message on error
            this.messages = this.messages.filter(m => m.id !== tempUserMsg.id);
            this.loading = false;
            throw error;
        }
    },

    newChat() {
        this.currentDialog = null;
        this.messages = [];
    },
});

// Products Store
Alpine.store('products', {
    items: [],
    current: null,
    loading: false,
    meta: {},

    async load(companyId, params = {}) {
        this.loading = true;
        const result = await products.list(companyId, params);
        this.items = result.products;
        this.meta = result.meta;
        this.loading = false;
    },

    async get(id) {
        this.loading = true;
        this.current = await products.get(id);
        this.loading = false;
        return this.current;
    },

    async create(data) {
        const product = await products.create(data);
        this.items.unshift(product);
        return product;
    },
});

// PWA Store - Enhanced with native mode detection
Alpine.store('pwa', {
    isInstalled: window.isPWAInstalled || false,
    isIOS: window.isIOS || false,
    isAndroid: window.isAndroid || false,
    platform: window.pwaDetector?.platform || 'web',

    get canInstall() {
        return 'BeforeInstallPromptEvent' in window;
    },

    get isPWAMode() {
        return this.isInstalled;
    },

    get isBrowserMode() {
        return !this.isInstalled;
    },

    checkStatus() {
        this.isInstalled = window.matchMedia('(display-mode: standalone)').matches ||
                          window.navigator.standalone === true ||
                          document.referrer.includes('android-app://');

        // Update platform detection
        if (window.pwaDetector) {
            this.isIOS = window.pwaDetector.isIOS;
            this.isAndroid = window.pwaDetector.isAndroid;
            this.platform = window.pwaDetector.platform;
        }

        return this.isInstalled;
    },

    // Platform-specific haptic feedback
    haptic(type = 'light') {
        if (window.haptic) {
            window.haptic[type]();
        }
    }
});

// UI Store - For managing UI state
Alpine.store('ui', {
    sidebarOpen: false,
    navPosition: safePersist('left', 'nav_position'), // 'left', 'right', 'bottom'
    sidebarCollapsed: safePersist(false, 'sidebar_collapsed'),

    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
        if (window.haptic) window.haptic.light();
    },

    closeSidebar() {
        this.sidebarOpen = false;
    },

    openSidebar() {
        this.sidebarOpen = true;
        if (window.haptic) window.haptic.light();
    },

    toggleSidebarCollapse() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
        if (window.haptic) window.haptic.light();
    },

    setNavPosition(position) {
        this.navPosition = position;
        if (window.haptic) window.haptic.selection();
    }
});

// Make Alpine available globally before starting
window.Alpine = Alpine;

// Start Alpine
Alpine.start();
