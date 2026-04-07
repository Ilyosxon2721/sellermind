@extends('storefront.layouts.app')

@section('page_title', 'Мой аккаунт — ' . $store->name)

@section('content')
@php $slug = $store->slug; $currency = $store->currency ?? 'сум'; @endphp

<div class="max-w-4xl mx-auto px-3 sm:px-4 lg:px-6 py-6 sm:py-8" x-data="mpAccount('{{ $slug }}')" x-init="load()">

    {{-- Загрузка --}}
    <template x-if="loading">
        <div class="flex items-center justify-center py-20">
            <svg class="animate-spin w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        </div>
    </template>

    {{-- Не авторизован → редирект --}}
    <template x-if="!loading && !customer">
        <div class="text-center py-20">
            <p class="text-gray-500 mb-4">Необходимо войти в аккаунт</p>
            <a :href="`/store/${slug}/login`" class="inline-block px-6 py-3 rounded-xl text-white font-semibold" style="background: var(--primary);">Войти</a>
        </div>
    </template>

    {{-- Личный кабинет --}}
    <template x-if="!loading && customer">
        <div>
            {{-- Шапка профиля --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center text-xl font-bold text-white" style="background: var(--primary);"
                         x-text="customer.name[0].toUpperCase()"></div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900" x-text="customer.name"></h1>
                        <p class="text-sm text-gray-500" x-text="customer.phone"></p>
                    </div>
                </div>
                <button @click="logout()" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition">Выйти</button>
            </div>

            {{-- Табы --}}
            <div class="flex gap-1 border-b border-gray-200 mb-6">
                <button @click="tab = 'orders'" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'orders' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'">
                    Мои заказы
                </button>
                <button @click="tab = 'profile'" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'profile' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'">
                    Профиль
                </button>
            </div>

            {{-- Таб: Заказы --}}
            <div x-show="tab === 'orders'">
                <template x-if="orders.length === 0">
                    <div class="text-center py-16">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Нет заказов</h3>
                        <p class="text-gray-500 mb-4">Вы ещё не совершали покупок</p>
                        <a :href="`/store/${slug}/catalog`" class="inline-block px-6 py-2.5 rounded-xl text-white font-semibold" style="background: var(--primary);">Перейти в каталог</a>
                    </div>
                </template>

                <div class="space-y-3">
                    <template x-for="order in orders" :key="order.id">
                        <a :href="`/store/${slug}/order/${order.order_number}`"
                           class="block bg-white rounded-xl border border-gray-100 p-4 hover:border-gray-200 hover:shadow-sm transition">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-mono font-semibold text-gray-900" x-text="order.order_number"></span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-blue-100 text-blue-700': order.status === 'new',
                                          'bg-green-100 text-green-700': order.status === 'confirmed' || order.status === 'delivered',
                                          'bg-yellow-100 text-yellow-700': order.status === 'processing',
                                          'bg-indigo-100 text-indigo-700': order.status === 'shipped',
                                          'bg-red-100 text-red-700': order.status === 'cancelled',
                                      }"
                                      x-text="({new:'Новый',confirmed:'Подтверждён',processing:'В обработке',shipped:'Отправлен',delivered:'Доставлен',cancelled:'Отменён'})[order.status] || order.status">
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500" x-text="new Date(order.created_at).toLocaleDateString('ru-RU')"></span>
                                <span class="font-bold text-gray-900" x-text="formatPrice(order.total) + ' {{ $currency }}'"></span>
                            </div>
                            <p class="mt-1.5 text-xs text-gray-400" x-text="(order.items || []).map(i => i.name).join(', ')"></p>
                        </a>
                    </template>
                </div>
            </div>

            {{-- Таб: Профиль --}}
            <div x-show="tab === 'profile'">
                <form @submit.prevent="saveProfile()" class="max-w-lg space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Имя</label>
                        <input type="text" x-model="profileForm.name" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                        <input type="email" x-model="profileForm.email" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Город</label>
                        <input type="text" x-model="profileForm.default_city" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Адрес доставки</label>
                        <input type="text" x-model="profileForm.default_address" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                    </div>

                    <p x-show="profileMsg" x-text="profileMsg" class="text-sm text-green-600 font-medium"></p>

                    <button type="submit" :disabled="saving" class="px-6 py-3 rounded-xl text-white font-semibold transition hover:opacity-90 disabled:opacity-50" style="background: var(--primary);">
                        <span x-show="!saving">Сохранить</span>
                        <span x-show="saving">Сохраняем...</span>
                    </button>
                </form>
            </div>
        </div>
    </template>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function mpAccount(slug) {
    return {
        loading: true,
        customer: null,
        orders: [],
        tab: 'orders',
        saving: false,
        profileMsg: '',
        profileForm: { name: '', email: '', default_city: '', default_address: '' },

        async load() {
            try {
                const [profileR, ordersR] = await Promise.all([
                    fetch(`/store/${slug}/api/customer/profile`, { headers: { Accept: 'application/json' } }),
                    fetch(`/store/${slug}/api/customer/orders`, { headers: { Accept: 'application/json' } }),
                ]);
                if (profileR.ok) {
                    const pd = await profileR.json();
                    this.customer = pd.data;
                    this.profileForm = {
                        name: this.customer.name || '',
                        email: this.customer.email || '',
                        default_city: this.customer.default_city || '',
                        default_address: this.customer.default_address || '',
                    };
                }
                if (ordersR.ok) {
                    const od = await ordersR.json();
                    this.orders = od.data || [];
                }
            } catch (e) {}
            this.loading = false;
        },

        async saveProfile() {
            this.saving = true; this.profileMsg = '';
            try {
                const r = await fetch(`/store/${slug}/api/customer/profile`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                    body: JSON.stringify(this.profileForm)
                });
                if (r.ok) {
                    const data = await r.json();
                    this.customer = data.data;
                    this.profileMsg = 'Профиль обновлён';
                    setTimeout(() => this.profileMsg = '', 3000);
                }
            } catch (e) {}
            this.saving = false;
        },

        async logout() {
            await fetch(`/store/${slug}/api/customer/logout`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
            });
            window.location.href = `/store/${slug}`;
        }
    }
}
</script>
@endsection
