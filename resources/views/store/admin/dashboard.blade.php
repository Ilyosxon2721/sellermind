@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-blue-50"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Мой магазин</h1>
                    <p class="text-sm text-gray-500">Управление интернет-магазинами</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2"
                            @click="loadStores()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Обновить</span>
                    </button>
                    <button class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl transition-all shadow-lg shadow-blue-500/25 flex items-center space-x-2"
                            @click="showCreateModal = true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Создать магазин</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6" x-data="storesDashboard()">
            {{-- Загрузка --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-20">
                    <svg class="animate-spin w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    <span class="ml-3 text-gray-500">Загрузка магазинов...</span>
                </div>
            </template>

            {{-- Пустое состояние --}}
            <template x-if="!loading && stores.length === 0">
                <div class="text-center py-20">
                    <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Нет магазинов</h3>
                    <p class="text-gray-500 mb-6">Создайте свой первый интернет-магазин для продажи товаров</p>
                    <button class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors font-medium"
                            @click="showCreateModal = true">
                        Создать магазин
                    </button>
                </div>
            </template>

            {{-- Список магазинов --}}
            <template x-if="!loading && stores.length > 0">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <template x-for="s in stores" :key="s.id">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow overflow-hidden">
                            {{-- Шапка карточки --}}
                            <div class="p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-900 truncate" x-text="s.name"></h3>
                                        <a :href="s.url" target="_blank" class="text-sm text-blue-600 hover:text-blue-700 truncate block" x-text="s.url"></a>
                                    </div>
                                    <div class="ml-3 flex items-center">
                                        <button @click="togglePublished(s)"
                                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                :class="s.is_published ? 'bg-blue-600' : 'bg-gray-200'">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                                  :class="s.is_published ? 'translate-x-6' : 'translate-x-1'"></span>
                                        </button>
                                    </div>
                                </div>

                                <p class="text-sm text-gray-500 line-clamp-2 mb-4" x-text="s.description || 'Нет описания'"></p>

                                {{-- Статистика --}}
                                <div class="flex items-center space-x-4 mb-4">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <span x-text="(s.products_count ?? 0) + ' товаров'"></span>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        <span x-text="(s.orders_count ?? 0) + ' заказов'"></span>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="s.is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                          x-text="s.is_published ? 'Опубликован' : 'Черновик'"></span>
                                </div>

                                {{-- Кнопка открытия витрины --}}
                                <a :href="'/store/' + s.slug" target="_blank"
                                   class="flex items-center justify-center space-x-2 w-full px-4 py-2.5 mb-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl transition-all text-sm font-medium shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    <span>Открыть магазин</span>
                                </a>

                                {{-- Ссылки на разделы --}}
                                <div class="grid grid-cols-3 gap-2">
                                    <a :href="'/my-store/' + s.id + '/theme'"
                                       class="flex flex-col items-center p-3 rounded-xl bg-gray-50 hover:bg-blue-50 hover:text-blue-700 transition-colors text-gray-600 text-xs font-medium">
                                        <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                                        Дизайн
                                    </a>
                                    <a :href="'/my-store/' + s.id + '/catalog'"
                                       class="flex flex-col items-center p-3 rounded-xl bg-gray-50 hover:bg-blue-50 hover:text-blue-700 transition-colors text-gray-600 text-xs font-medium">
                                        <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        Каталог
                                    </a>
                                    <a :href="'/my-store/' + s.id + '/orders'"
                                       class="flex flex-col items-center p-3 rounded-xl bg-gray-50 hover:bg-blue-50 hover:text-blue-700 transition-colors text-gray-600 text-xs font-medium">
                                        <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        Заказы
                                    </a>
                                    <a :href="'/my-store/' + s.id + '/banners'"
                                       class="flex flex-col items-center p-3 rounded-xl bg-gray-50 hover:bg-blue-50 hover:text-blue-700 transition-colors text-gray-600 text-xs font-medium">
                                        <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Баннеры
                                    </a>
                                    <a :href="'/my-store/' + s.id + '/analytics'"
                                       class="flex flex-col items-center p-3 rounded-xl bg-gray-50 hover:bg-blue-50 hover:text-blue-700 transition-colors text-gray-600 text-xs font-medium">
                                        <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                        Аналитика
                                    </a>
                                    <button @click="confirmDelete(s)"
                                            class="flex flex-col items-center p-3 rounded-xl bg-gray-50 hover:bg-red-50 hover:text-red-600 transition-colors text-gray-400 text-xs font-medium">
                                        <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Удалить
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Модал создания --}}
            <div x-show="showCreateModal" x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 z-10"
                         @click.away="showCreateModal = false">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-semibold text-gray-900">Создать магазин</h2>
                            <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Название магазина *</label>
                                <input type="text" x-model="form.name"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Мой магазин">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                                <textarea x-model="form.description" rows="3"
                                          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Краткое описание вашего магазина"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
                                    <input type="tel" x-model="form.phone"
                                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="+998 90 123 45 67">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" x-model="form.email"
                                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="shop@example.com">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                            <button @click="showCreateModal = false"
                                    class="px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors font-medium">
                                Отмена
                            </button>
                            <button @click="createStore()"
                                    :disabled="saving"
                                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors font-medium disabled:opacity-50">
                                <span x-show="!saving">Создать</span>
                                <span x-show="saving" class="flex items-center">
                                    <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Создание...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Модал подтверждения удаления --}}
            <div x-show="showDeleteModal" x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 z-10">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Удалить магазин?</h3>
                            <p class="text-sm text-gray-500 mb-6">Магазин <strong x-text="deleteTarget?.name"></strong> будет удален безвозвратно вместе со всеми товарами и заказами.</p>
                        </div>
                        <div class="flex items-center justify-center space-x-3">
                            <button @click="showDeleteModal = false"
                                    class="px-6 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors font-medium">
                                Отмена
                            </button>
                            <button @click="deleteStore()"
                                    :disabled="saving"
                                    class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors font-medium disabled:opacity-50">
                                Удалить
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function storesDashboard() {
    return {
        stores: [],
        loading: true,
        saving: false,
        showCreateModal: false,
        showDeleteModal: false,
        deleteTarget: null,
        form: {
            name: '',
            description: '',
            phone: '',
            email: '',
        },

        init() {
            this.loadStores();
        },

        async loadStores() {
            this.loading = true;
            try {
                const res = await window.api.get('/store/stores');
                this.stores = res.data.data ?? res.data;
            } catch (e) {
                window.toast?.error('Не удалось загрузить магазины');
            } finally {
                this.loading = false;
            }
        },

        async createStore() {
            if (!this.form.name.trim()) {
                window.toast?.error('Укажите название магазина');
                return;
            }
            this.saving = true;
            try {
                await window.api.post('/store/stores', this.form);
                window.toast?.success('Магазин создан');
                this.showCreateModal = false;
                this.form = { name: '', description: '', phone: '', email: '' };
                await this.loadStores();
            } catch (e) {
                const msg = e.response?.data?.message || 'Ошибка при создании магазина';
                window.toast?.error(msg);
            } finally {
                this.saving = false;
            }
        },

        async togglePublished(store) {
            try {
                await window.api.put(`/store/stores/${store.id}`, {
                    is_published: !store.is_published,
                });
                store.is_published = !store.is_published;
                window.toast?.success(store.is_published ? 'Магазин опубликован' : 'Магазин скрыт');
            } catch (e) {
                window.toast?.error('Не удалось изменить статус');
            }
        },

        confirmDelete(store) {
            this.deleteTarget = store;
            this.showDeleteModal = true;
        },

        async deleteStore() {
            if (!this.deleteTarget) return;
            this.saving = true;
            try {
                await window.api.delete(`/store/stores/${this.deleteTarget.id}`);
                window.toast?.success('Магазин удален');
                this.showDeleteModal = false;
                this.deleteTarget = null;
                await this.loadStores();
            } catch (e) {
                window.toast?.error('Не удалось удалить магазин');
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endsection
