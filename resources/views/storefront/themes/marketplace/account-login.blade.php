@extends('storefront.layouts.app')

@section('page_title', 'Вход — ' . $store->name)

@section('content')
@php $slug = $store->slug; @endphp

<div class="max-w-md mx-auto px-4 py-12" x-data="mpAuth('{{ $slug }}')">

    {{-- Переключатель вход/регистрация --}}
    <div class="flex rounded-xl bg-gray-100 p-1 mb-8">
        <button @click="mode = 'login'" class="flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all"
                :class="mode === 'login' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'">Вход</button>
        <button @click="mode = 'register'" class="flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all"
                :class="mode === 'register' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'">Регистрация</button>
    </div>

    {{-- ФОРМА ВХОДА --}}
    <template x-if="mode === 'login'">
        <form @submit.prevent="login()" class="space-y-5">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Вход в аккаунт</h1>
                <p class="text-sm text-gray-500 mt-1">Введите телефон и пароль</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Телефон</label>
                <input type="tel" x-model="loginForm.phone" required placeholder="+998 90 123 45 67"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color: var(--primary);">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Пароль</label>
                <input type="password" x-model="loginForm.password" required
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color: var(--primary);">
            </div>

            <p x-show="error" x-text="error" class="text-sm text-red-600 font-medium"></p>

            <button type="submit" :disabled="loading" class="w-full py-3.5 rounded-xl text-white font-bold text-base transition hover:opacity-90 disabled:opacity-50" style="background: var(--primary);">
                <span x-show="!loading">Войти</span>
                <span x-show="loading">Входим...</span>
            </button>
        </form>
    </template>

    {{-- ФОРМА РЕГИСТРАЦИИ --}}
    <template x-if="mode === 'register'">
        <form @submit.prevent="register()" class="space-y-5">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Регистрация</h1>
                <p class="text-sm text-gray-500 mt-1">Создайте аккаунт для отслеживания заказов</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Имя</label>
                <input type="text" x-model="regForm.name" required
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color: var(--primary);">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Телефон</label>
                <input type="tel" x-model="regForm.phone" required placeholder="+998 90 123 45 67"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color: var(--primary);">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email <span class="text-gray-400">(необязательно)</span></label>
                <input type="email" x-model="regForm.email"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color: var(--primary);">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Пароль</label>
                <input type="password" x-model="regForm.password" required minlength="6"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color: var(--primary);">
            </div>

            <p x-show="error" x-text="error" class="text-sm text-red-600 font-medium"></p>

            <button type="submit" :disabled="loading" class="w-full py-3.5 rounded-xl text-white font-bold text-base transition hover:opacity-90 disabled:opacity-50" style="background: var(--primary);">
                <span x-show="!loading">Зарегистрироваться</span>
                <span x-show="loading">Регистрация...</span>
            </button>
        </form>
    </template>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function mpAuth(slug) {
    return {
        mode: 'login',
        loading: false,
        error: '',
        loginForm: { phone: '', password: '' },
        regForm: { name: '', phone: '', email: '', password: '' },

        async login() {
            this.loading = true; this.error = '';
            try {
                const r = await fetch(`/store/${slug}/api/customer/login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                    body: JSON.stringify(this.loginForm)
                });
                const data = await r.json();
                if (r.ok) { window.location.href = `/store/${slug}/account`; }
                else { this.error = data.message || 'Неверные данные'; }
            } catch (e) { this.error = 'Ошибка сети'; }
            this.loading = false;
        },

        async register() {
            this.loading = true; this.error = '';
            try {
                const r = await fetch(`/store/${slug}/api/customer/register`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                    body: JSON.stringify(this.regForm)
                });
                const data = await r.json();
                if (r.ok) { window.location.href = `/store/${slug}/account`; }
                else { this.error = data.message || Object.values(data.errors || {})[0]?.[0] || 'Ошибка'; }
            } catch (e) { this.error = 'Ошибка сети'; }
            this.loading = false;
        }
    }
}
</script>
@endsection
