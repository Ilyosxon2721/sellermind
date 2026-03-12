@extends('layouts.app')

@section('content')
<div x-data="profilePage()" x-init="init()" class="flex h-screen bg-gray-50"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar></x-sidebar>
    </template>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('admin.profile') }}</h1>
                    <p class="text-sm text-gray-500" x-text="$store.auth.user?.email || ''"></p>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="max-w-2xl mx-auto space-y-6">

                <!-- Профиль -->
                <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-2xl font-bold flex-shrink-0"
                             x-text="($store.auth.user?.name || '?')[0].toUpperCase()">
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-gray-900 truncate" x-text="$store.auth.user?.name || ''"></h2>
                            <p class="text-sm text-gray-500 truncate" x-text="$store.auth.user?.email || ''"></p>
                        </div>
                    </div>

                    <!-- Редактирование -->
                    <form @submit.prevent="updateProfile()" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.name') }}</label>
                            <input type="text" x-model="form.name"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.email') }}</label>
                            <input type="email" x-model="form.email"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.phone') }}</label>
                            <input type="tel" x-model="form.phone"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <x-ui.button type="submit" :disabled="saving">
                            <span x-show="!saving">{{ __('app.save') }}</span>
                            <span x-show="saving">{{ __('app.saving') }}...</span>
                        </x-ui.button>
                    </form>
                </div>

                <!-- Компания -->
                <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('admin.company_profile') }}</h3>
                    <div class="space-y-3">
                        <template x-for="company in $store.auth.companies" :key="company.id">
                            <div class="flex items-center justify-between p-3 rounded-lg border transition"
                                 :class="company.id === $store.auth.currentCompany?.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:bg-gray-50 cursor-pointer'"
                                 @click="$store.auth.switchCompany(company.id)">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate" x-text="company.name"></p>
                                    <p class="text-xs text-gray-500" x-text="company.inn ? 'ИНН: ' + company.inn : ''"></p>
                                </div>
                                <svg x-show="company.id === $store.auth.currentCompany?.id" class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Выход -->
                <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                    <button @click="$store.auth.logout(); window.location.href='/login'"
                            class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="font-medium">{{ __('admin.logout') }}</span>
                    </button>
                </div>

            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
function profilePage() {
    return {
        form: { name: '', email: '', phone: '' },
        saving: false,

        init() {
            const user = Alpine.store('auth').user;
            if (user) {
                this.form.name = user.name || '';
                this.form.email = user.email || '';
                this.form.phone = user.phone || '';
            }
        },

        async updateProfile() {
            this.saving = true;
            try {
                const res = await fetch('/api/profile', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.form),
                });
                if (res.ok) {
                    const data = await res.json();
                    Alpine.store('auth').user = data.user || data;
                }
            } catch (e) {
                console.error('Profile update failed', e);
            } finally {
                this.saving = false;
            }
        }
    };
}
</script>
@endpush
