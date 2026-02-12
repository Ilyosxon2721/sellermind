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
                <div class="flex items-center space-x-4">
                    <a href="/my-store" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Баннеры</h1>
                        <p class="text-sm text-gray-500">Управление баннерами магазина</p>
                    </div>
                </div>
                <button @click="openModal()"
                        class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl transition-all shadow-lg shadow-blue-500/25 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Добавить баннер</span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6" x-data="bannersManager({{ $storeId ?? 'null' }})">
            {{-- Загрузка --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-20">
                    <svg class="animate-spin w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                </div>
            </template>

            {{-- Пустое состояние --}}
            <template x-if="!loading && banners.length === 0">
                <div class="text-center py-20">
                    <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Нет баннеров</h3>
                    <p class="text-gray-500 mb-6">Добавьте баннеры для главной страницы вашего магазина</p>
                    <button @click="openModal()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors font-medium">
                        Добавить баннер
                    </button>
                </div>
            </template>

            {{-- Сетка баннеров --}}
            <template x-if="!loading && banners.length > 0">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <template x-for="(b, index) in banners" :key="b.id">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow overflow-hidden group"
                             draggable="true"
                             @dragstart="dragStart(index, $event)"
                             @dragover.prevent="dragOver(index, $event)"
                             @drop.prevent="drop(index, $event)"
                             @dragend="dragEnd()">
                            {{-- Превью баннера --}}
                            <div class="relative aspect-[16/9] bg-gray-100 overflow-hidden">
                                <img x-show="b.image" :src="b.image" :alt="b.title" class="w-full h-full object-cover">
                                <div x-show="!b.image" class="w-full h-full flex items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                {{-- Оверлей с текстом --}}
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent flex flex-col justify-end p-4">
                                    <h3 class="text-white font-semibold text-sm" x-text="b.title || 'Без заголовка'"></h3>
                                    <p x-show="b.subtitle" class="text-white/80 text-xs mt-1" x-text="b.subtitle"></p>
                                </div>
                                {{-- Позиция --}}
                                <div class="absolute top-2 left-2 px-2 py-1 bg-black/50 text-white text-xs rounded-lg font-medium cursor-grab"
                                     x-text="'#' + (index + 1)"></div>
                                {{-- Статус --}}
                                <div class="absolute top-2 right-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                          :class="b.is_active ? 'bg-green-500 text-white' : 'bg-gray-500 text-white'"
                                          x-text="b.is_active ? 'Активен' : 'Выключен'"></span>
                                </div>
                            </div>

                            {{-- Информация --}}
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-xs text-gray-500 space-x-3">
                                        <span x-show="b.start_date" x-text="'С: ' + formatDate(b.start_date)"></span>
                                        <span x-show="b.end_date" x-text="'До: ' + formatDate(b.end_date)"></span>
                                    </div>
                                    <span x-show="b.position" class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full" x-text="positionLabel(b.position)"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <a x-show="b.url" :href="b.url" target="_blank" class="text-xs text-blue-600 hover:text-blue-700 truncate max-w-[60%]" x-text="b.url"></a>
                                    <span x-show="!b.url" class="text-xs text-gray-400">Нет ссылки</span>
                                    <div class="flex items-center space-x-1">
                                        <button @click="editBanner(b)" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Редактировать">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click="deleteBanner(b.id)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Удалить">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Модал --}}
            <div x-show="showModal" x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full p-6 z-10 max-h-[90vh] overflow-y-auto">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-semibold text-gray-900" x-text="editingId ? 'Редактировать баннер' : 'Новый баннер'"></h2>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Заголовок</label>
                                    <input type="text" x-model="form.title" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Заголовок баннера">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Подзаголовок</label>
                                    <input type="text" x-model="form.subtitle" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Дополнительный текст">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">URL изображения *</label>
                                <input type="url" x-model="form.image" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="https://example.com/banner.jpg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">URL мобильного изображения</label>
                                <input type="url" x-model="form.image_mobile" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="https://example.com/banner-mobile.jpg">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Ссылка при клике</label>
                                    <input type="url" x-model="form.url" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="https://...">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Текст кнопки</label>
                                    <input type="text" x-model="form.button_text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Подробнее">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Позиция</label>
                                <select x-model="form.position" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="hero">Главный слайдер</option>
                                    <option value="top">Верх страницы</option>
                                    <option value="middle">Середина страницы</option>
                                    <option value="bottom">Низ страницы</option>
                                    <option value="sidebar">Боковая панель</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Дата начала</label>
                                    <input type="date" x-model="form.start_date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Дата окончания</label>
                                    <input type="date" x-model="form.end_date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" x-model="form.is_active" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700">Активен</span>
                            </label>

                            {{-- Превью --}}
                            <div x-show="form.image" class="border border-gray-200 rounded-xl overflow-hidden">
                                <p class="text-xs text-gray-500 px-3 py-2 bg-gray-50 border-b border-gray-200">Превью</p>
                                <div class="aspect-[16/9] bg-gray-100">
                                    <img :src="form.image" class="w-full h-full object-cover" @error="$el.style.display='none'">
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                            <button @click="showModal = false" class="px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors font-medium">Отмена</button>
                            <button @click="saveBanner()" :disabled="saving" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors font-medium disabled:opacity-50">
                                <span x-text="editingId ? 'Сохранить' : 'Создать'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function bannersManager(storeId) {
    return {
        storeId,
        loading: true,
        saving: false,
        banners: [],
        showModal: false,
        editingId: null,
        draggedIndex: null,
        form: {
            title: '', subtitle: '', image: '', image_mobile: '', url: '', button_text: '',
            position: 'hero', is_active: true, start_date: '', end_date: '',
        },

        init() {
            this.loadBanners();
        },

        async loadBanners() {
            this.loading = true;
            try {
                const res = await window.api.get(`/store/stores/${this.storeId}/banners`);
                this.banners = res.data.data ?? res.data;
            } catch (e) {
                window.toast?.error('Не удалось загрузить баннеры');
            } finally {
                this.loading = false;
            }
        },

        openModal() {
            this.editingId = null;
            this.form = {
                title: '', subtitle: '', image: '', image_mobile: '', url: '', button_text: '',
                position: 'hero', is_active: true, start_date: '', end_date: '',
            };
            this.showModal = true;
        },

        editBanner(b) {
            this.editingId = b.id;
            this.form = {
                title: b.title || '',
                subtitle: b.subtitle || '',
                image: b.image || '',
                image_mobile: b.image_mobile || '',
                url: b.url || '',
                button_text: b.button_text || '',
                position: b.position || 'hero',
                is_active: b.is_active,
                start_date: b.start_date ? b.start_date.substring(0, 10) : '',
                end_date: b.end_date ? b.end_date.substring(0, 10) : '',
            };
            this.showModal = true;
        },

        async saveBanner() {
            if (!this.form.image.trim()) { window.toast?.error('Укажите URL изображения'); return; }
            this.saving = true;
            try {
                if (this.editingId) {
                    await window.api.put(`/store/stores/${this.storeId}/banners/${this.editingId}`, this.form);
                    window.toast?.success('Баннер обновлен');
                } else {
                    await window.api.post(`/store/stores/${this.storeId}/banners`, this.form);
                    window.toast?.success('Баннер создан');
                }
                this.showModal = false;
                await this.loadBanners();
            } catch (e) {
                window.toast?.error(e.response?.data?.message || 'Ошибка сохранения');
            } finally {
                this.saving = false;
            }
        },

        async deleteBanner(id) {
            if (!confirm('Удалить баннер?')) return;
            try {
                await window.api.delete(`/store/stores/${this.storeId}/banners/${id}`);
                this.banners = this.banners.filter(b => b.id !== id);
                window.toast?.success('Баннер удален');
            } catch (e) {
                window.toast?.error('Ошибка удаления');
            }
        },

        // Drag and drop для сортировки
        dragStart(index, e) {
            this.draggedIndex = index;
            e.dataTransfer.effectAllowed = 'move';
            e.target.closest('[draggable]').classList.add('opacity-50');
        },

        dragOver(index, e) {
            e.dataTransfer.dropEffect = 'move';
        },

        async drop(index, e) {
            if (this.draggedIndex === null || this.draggedIndex === index) return;
            const moved = this.banners.splice(this.draggedIndex, 1)[0];
            this.banners.splice(index, 0, moved);
            this.draggedIndex = null;

            // Сохраняем порядок
            const order = this.banners.map((b, i) => ({ id: b.id, sort_order: i }));
            try {
                await window.api.post(`/store/stores/${this.storeId}/banners/reorder`, { order });
                window.toast?.success('Порядок сохранен');
            } catch (e) {
                window.toast?.error('Не удалось сохранить порядок');
            }
        },

        dragEnd() {
            this.draggedIndex = null;
            document.querySelectorAll('[draggable]').forEach(el => el.classList.remove('opacity-50'));
        },

        positionLabel(position) {
            const map = { hero: 'Слайдер', top: 'Верх', middle: 'Середина', bottom: 'Низ', sidebar: 'Сайдбар' };
            return map[position] || position;
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: '2-digit' });
        },
    };
}
</script>
@endsection
