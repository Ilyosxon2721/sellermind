import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Add token to requests
api.interceptors.request.use((config) => {
    // Try Alpine persist format first, then fallback to regular
    let token = localStorage.getItem('_x_auth_token');
    if (token) {
        // Alpine persist stores as JSON string
        try {
            token = JSON.parse(token);
        } catch (e) {
            // Not JSON, use as-is
        }
    }
    if (!token) {
        token = localStorage.getItem('auth_token');
    }
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Handle auth errors
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Clear all auth data
            localStorage.removeItem('_x_auth_token');
            localStorage.removeItem('_x_auth_user');
            localStorage.removeItem('_x_current_company');
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            // Only redirect if not already on login page
            if (!window.location.pathname.includes('/login')) {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

export const auth = {
    async register(data) {
        const response = await api.post('/auth/register', data);
        // Alpine store will persist the token via $persist
        return response.data;
    },

    async login(email, password) {
        const response = await api.post('/auth/login', { email, password });
        // Alpine store will persist the token via $persist
        return response.data;
    },

    async logout() {
        try {
            await api.post('/auth/logout');
        } catch (e) {
            // Ignore logout errors
        }
        // Clear all auth data
        localStorage.removeItem('_x_auth_token');
        localStorage.removeItem('_x_auth_user');
        localStorage.removeItem('_x_current_company');
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
    },

    async me() {
        const response = await api.get('/me');
        return response.data;
    },

    getUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    },

    isAuthenticated() {
        return !!localStorage.getItem('auth_token');
    },
};

export const companies = {
    async list() {
        const response = await api.get('/companies');
        return response.data.companies;
    },

    async create(data) {
        const response = await api.post('/companies', data);
        return response.data.company;
    },
};

export const products = {
    async list(companyId, params = {}) {
        const response = await api.get('/products', { params: { company_id: companyId, ...params } });
        return response.data;
    },

    async get(id) {
        const response = await api.get(`/products/${id}`);
        return response.data.product;
    },

    async create(data) {
        const response = await api.post('/products', data);
        return response.data.product;
    },

    async update(id, data) {
        const response = await api.put(`/products/${id}`, data);
        return response.data.product;
    },

    async delete(id) {
        await api.delete(`/products/${id}`);
    },
};

export const dialogs = {
    async list(companyId, params = {}) {
        const response = await api.get('/dialogs', { params: { company_id: companyId, ...params } });
        return response.data;
    },

    async get(id) {
        const response = await api.get(`/dialogs/${id}`);
        return response.data.dialog;
    },

    async create(companyId, title = null, category = 'general', isPrivate = false) {
        const response = await api.post('/dialogs', { company_id: companyId, title, category, is_private: isPrivate });
        return response.data.dialog;
    },

    async hide(id) {
        const response = await api.post(`/dialogs/${id}/hide`);
        return response.data;
    },
};

export const chat = {
    async send(data) {
        const response = await api.post('/chat', data);
        return response.data;
    },

    async generateCard(data) {
        const response = await api.post('/chat/generate-card', data);
        return response.data.card;
    },

    async generateReviewResponse(data) {
        const response = await api.post('/chat/generate-review-response', data);
        return response.data.responses;
    },
};

export const images = {
    async upload(productId, file, isPrimary = false) {
        const formData = new FormData();
        formData.append('image', file);
        formData.append('is_primary', isPrimary);

        const response = await api.post(`/products/${productId}/images/upload`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return response.data.image;
    },

    async generate(productId, prompt, quality = 'medium', count = 1) {
        const response = await api.post(`/products/${productId}/images/generate`, {
            prompt, quality, count,
        });
        return response.data.images;
    },
};

export const tasks = {
    async list(companyId, params = {}) {
        const response = await api.get('/agent/tasks', { params: { company_id: companyId, ...params } });
        return response.data;
    },

    async get(id) {
        const response = await api.get(`/agent/tasks/${id}`);
        return response.data.task;
    },

    async create(companyId, type, inputData) {
        const response = await api.post('/agent/tasks', {
            company_id: companyId,
            type,
            input_data: inputData,
        });
        return response.data.task;
    },
};

export default api;
