@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-blue-50" x-data="warehouseCreatePage()">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-1">
                        <a href="/warehouse/list" class="hover:text-blue-600 transition-colors">Склады</a>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span class="text-gray-900">Новый склад</span>
                    </nav>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Создание склада</h1>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="/warehouse/list" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-all duration-200">Отмена</a>
                    <button @click="save()" :disabled="saving" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl shadow-lg shadow-blue-500/25 transition-all duration-200 flex items-center space-x-2 disabled:opacity-50">
                        <template x-if="saving">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </template>
                        <span x-text="saving ? 'Сохранение...' : 'Создать склад'"></span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6">
            <!-- Toast -->
            <div x-show="toast.show" x-transition class="fixed top-4 right-4 z-50 max-w-sm">
                <div :class="toast.type === 'error' ? 'bg-red-500' : 'bg-green-500'" class="text-white px-6 py-4 rounded-xl shadow-lg flex items-center space-x-3">
                    <span x-text="toast.message"></span>
                    <button @click="toast.show = false" class="ml-auto hover:opacity-75">×</button>
                </div>
            </div>

            <div class="max-w-3xl mx-auto space-y-6">
                <!-- Basic Info -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Основная информация</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Название *</label>
                            <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" x-model="form.name" placeholder="Основной склад">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Код</label>
                            <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" x-model="form.code" placeholder="main">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Адрес</label>
                            <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" x-model="form.address" placeholder="ул. Примерная, д. 1">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Комментарий к адресу</label>
                            <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" x-model="form.address_comment" placeholder="Вход через ворота">
                        </div>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Дополнительно</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Группа</label>
                            <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" x-model="form.group_name" placeholder="FBS / FBO">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Внешний код</label>
                            <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" x-model="form.external_code" placeholder="Для интеграций">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Комментарий</label>
                            <textarea class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" rows="2" x-model="form.comment" placeholder="Любой комментарий"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Настройки</h2>
                    <div class="flex flex-wrap gap-6">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" x-model="form.is_default">
                            <span class="text-gray-700">Сделать основным складом</span>
                        </label>
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" x-model="form.is_active">
                            <span class="text-gray-700">Активен</span>
                        </label>
                    </div>
                </div>

                <!-- Zones (Optional) -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Зоны и ячейки</h2>
                            <p class="text-sm text-gray-500">Необязательно. Для адресного хранения.</p>
                        </div>
                        <button type="button" @click="addZone()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors text-sm">
                            + Добавить зону
                        </button>
                    </div>
                    <div class="space-y-4" x-show="form.meta.zones.length > 0">
                        <template x-for="(zone, zIdx) in form.meta.zones" :key="zIdx">
                            <div class="border border-gray-200 rounded-xl p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <input type="text" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="zone.name" placeholder="Название зоны">
                                    <button type="button" @click="removeZone(zIdx)" class="ml-2 px-3 py-2 bg-red-100 text-red-600 rounded-lg text-sm hover:bg-red-200 transition-colors">Удалить</button>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">Ячейки</span>
                                    <button type="button" @click="addBin(zIdx)" class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs hover:bg-gray-200 transition-colors">+ Ячейка</button>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                    <template x-for="(bin, bIdx) in zone.bins" :key="bIdx">
                                        <div class="border border-gray-200 rounded-lg p-2 space-y-1">
                                            <input type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-xs" x-model="bin.name" placeholder="Ячейка">
                                            <input type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-xs" x-model="bin.barcode" placeholder="Штрихкод">
                                            <button type="button" @click="removeBin(zIdx, bIdx)" class="w-full text-center px-2 py-1 bg-red-50 text-red-600 rounded text-xs hover:bg-red-100 transition-colors">×</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    function warehouseCreatePage() {
        return {
            saving: false,
            toast: { show: false, message: '', type: 'success' },
            form: {
                name: '',
                code: '',
                address: '',
                address_comment: '',
                comment: '',
                group_name: '',
                external_code: '',
                is_default: false,
                is_active: true,
                meta: { zones: [] },
            },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
            },


            addZone() {
                this.form.meta.zones.push({ name: '', bins: [] });
            },
            removeZone(idx) {
                this.form.meta.zones.splice(idx, 1);
            },
            addBin(zIdx) {
                this.form.meta.zones[zIdx].bins.push({ name: '', barcode: '', status: 'ACTIVE' });
            },
            removeBin(zIdx, bIdx) {
                this.form.meta.zones[zIdx].bins.splice(bIdx, 1);
            },

            async save() {
                if (!this.form.name.trim()) {
                    this.showToast('Название склада обязательно', 'error');
                    return;
                }

                // Check if Alpine store is available and has currentCompany
                const authStore = this.$store.auth;
                if (!authStore || !authStore.currentCompany) {
                    this.showToast('Нет активной компании. Пожалуйста, создайте компанию в профиле.', 'error');
                    return;
                }

                this.saving = true;
                try {
                    const payload = {
                        ...this.form,
                        company_id: authStore.currentCompany.id
                    };
                    if (payload.meta && payload.meta.zones) {
                        payload.meta_json = payload.meta;
                    }

                    const resp = await fetch('/api/warehouse', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${authStore.token}`
                        },
                        body: JSON.stringify(payload)
                    });

                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || json.message || 'Ошибка сохранения');
                    this.showToast('Склад создан!', 'success');
                    setTimeout(() => { window.location.href = '/warehouse/list'; }, 1000);
                } catch(e) {
                    console.error(e);
                    this.showToast(e.message, 'error');
                } finally {
                    this.saving = false;
                }
            }
        }
    }
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="warehouseCreatePage()" style="background: #f2f2f7;">
    <x-pwa-header title="Новый склад" :backUrl="'/warehouse/list'">
        <button @click="save()" :disabled="saving" class="native-header-btn text-blue-600" onclick="if(window.haptic) window.haptic.light()">
            <span x-show="!saving">Сохранить</span>
            <span x-show="saving">...</span>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">

        {{-- Toast --}}
        <div x-show="toast.show" x-transition class="fixed top-16 left-4 right-4 z-50">
            <div :class="toast.type === 'error' ? 'bg-red-500' : 'bg-green-500'" class="text-white px-4 py-3 rounded-xl shadow-lg text-center">
                <span x-text="toast.message"></span>
            </div>
        </div>

        <div class="px-4 py-4 space-y-4">
            {{-- Basic Info --}}
            <div class="native-card space-y-3">
                <p class="native-body font-semibold">Основная информация</p>
                <div>
                    <label class="native-caption">Название *</label>
                    <input type="text" class="native-input mt-1" x-model="form.name" placeholder="Основной склад">
                </div>
                <div>
                    <label class="native-caption">Код</label>
                    <input type="text" class="native-input mt-1" x-model="form.code" placeholder="main">
                </div>
                <div>
                    <label class="native-caption">Адрес</label>
                    <input type="text" class="native-input mt-1" x-model="form.address" placeholder="ул. Примерная, д. 1">
                </div>
            </div>

            {{-- Additional Info --}}
            <div class="native-card space-y-3">
                <p class="native-body font-semibold">Дополнительно</p>
                <div>
                    <label class="native-caption">Группа</label>
                    <input type="text" class="native-input mt-1" x-model="form.group_name" placeholder="FBS / FBO">
                </div>
                <div>
                    <label class="native-caption">Комментарий</label>
                    <textarea class="native-input mt-1" rows="2" x-model="form.comment" placeholder="Любой комментарий"></textarea>
                </div>
            </div>

            {{-- Settings --}}
            <div class="native-card space-y-3">
                <p class="native-body font-semibold">Настройки</p>
                <label class="flex items-center space-x-3">
                    <input type="checkbox" class="w-5 h-5 rounded border-gray-300 text-blue-600" x-model="form.is_default">
                    <span class="native-body">Основной склад</span>
                </label>
                <label class="flex items-center space-x-3">
                    <input type="checkbox" class="w-5 h-5 rounded border-gray-300 text-blue-600" x-model="form.is_active">
                    <span class="native-body">Активен</span>
                </label>
            </div>

            {{-- Save Button --}}
            <button class="native-btn w-full" @click="save()" :disabled="saving">
                <span x-show="!saving">Создать склад</span>
                <span x-show="saving">Сохранение...</span>
            </button>
        </div>
    </main>
</div>
@endsection
