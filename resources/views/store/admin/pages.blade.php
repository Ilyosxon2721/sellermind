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
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Страницы</h1>
                        <p class="text-sm text-gray-500">Статические страницы магазина</p>
                    </div>
                </div>
                <button @click="openModal()"
                        class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl transition-all shadow-lg shadow-blue-500/25 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Новая страница</span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6" x-data="pagesManager({{ $storeId ?? 'null' }})">
            {{-- Загрузка --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-20">
                    <svg class="animate-spin w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                </div>
            </template>

            {{-- Таблица --}}
            <template x-if="!loading">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Заголовок</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Slug</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">В меню</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">В подвале</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Активна</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-if="pages.length === 0">
                                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">Нет страниц</td></tr>
                                </template>
                                <template x-for="p in pages" :key="p.id">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="p.title"></td>
                                        <td class="px-6 py-4 text-sm text-gray-500 font-mono" x-text="p.slug"></td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                  :class="p.show_in_menu ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'"
                                                  x-text="p.show_in_menu ? 'Да' : 'Нет'"></span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                  :class="p.show_in_footer ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'"
                                                  x-text="p.show_in_footer ? 'Да' : 'Нет'"></span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                  :class="p.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                                  x-text="p.is_active ? 'Да' : 'Нет'"></span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button @click="editPage(p)" class="text-blue-500 hover:text-blue-700 transition-colors" title="Редактировать">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button @click="deletePage(p.id)" class="text-red-400 hover:text-red-600 transition-colors" title="Удалить">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
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
                            <h2 class="text-xl font-semibold text-gray-900" x-text="editingId ? 'Редактировать страницу' : 'Новая страница'"></h2>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Заголовок *</label>
                                <input type="text" x-model="form.title"
                                       @input="if (!editingId) form.slug = slugify(form.title)"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="О нас">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                                <input type="text" x-model="form.slug" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm" placeholder="o-nas">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Содержимое *</label>
                                <textarea x-model="form.content" rows="12"
                                          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                          placeholder="Текст страницы... (поддерживается HTML)"></textarea>
                            </div>
                            <div class="flex items-center space-x-6">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" x-model="form.show_in_menu" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Показывать в меню</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" x-model="form.show_in_footer" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Показывать в подвале</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" x-model="form.is_active" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Активна</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                            <button @click="showModal = false" class="px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors font-medium">Отмена</button>
                            <button @click="savePage()" :disabled="saving" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors font-medium disabled:opacity-50">
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
function pagesManager(storeId) {
    return {
        storeId,
        loading: true,
        saving: false,
        pages: [],
        showModal: false,
        editingId: null,
        form: { title: '', slug: '', content: '', show_in_menu: false, show_in_footer: false, is_active: true },

        init() {
            this.loadPages();
        },

        async loadPages() {
            this.loading = true;
            try {
                const res = await window.api.get(`/api/store/stores/${this.storeId}/pages`);
                this.pages = res.data.data ?? res.data;
            } catch (e) {
                window.toast?.error('Не удалось загрузить страницы');
            } finally {
                this.loading = false;
            }
        },

        openModal() {
            this.editingId = null;
            this.form = { title: '', slug: '', content: '', show_in_menu: false, show_in_footer: false, is_active: true };
            this.showModal = true;
        },

        editPage(p) {
            this.editingId = p.id;
            this.form = {
                title: p.title,
                slug: p.slug,
                content: p.content || '',
                show_in_menu: p.show_in_menu,
                show_in_footer: p.show_in_footer,
                is_active: p.is_active,
            };
            this.showModal = true;
        },

        async savePage() {
            if (!this.form.title.trim()) { window.toast?.error('Укажите заголовок'); return; }
            if (!this.form.content.trim()) { window.toast?.error('Укажите содержимое'); return; }
            this.saving = true;
            try {
                if (this.editingId) {
                    await window.api.put(`/api/store/stores/${this.storeId}/pages/${this.editingId}`, this.form);
                    window.toast?.success('Страница обновлена');
                } else {
                    await window.api.post(`/api/store/stores/${this.storeId}/pages`, this.form);
                    window.toast?.success('Страница создана');
                }
                this.showModal = false;
                await this.loadPages();
            } catch (e) {
                window.toast?.error(e.response?.data?.message || 'Ошибка сохранения');
            } finally {
                this.saving = false;
            }
        },

        async deletePage(id) {
            if (!confirm('Удалить страницу?')) return;
            try {
                await window.api.delete(`/api/store/stores/${this.storeId}/pages/${id}`);
                this.pages = this.pages.filter(p => p.id !== id);
                window.toast?.success('Страница удалена');
            } catch (e) {
                window.toast?.error('Ошибка удаления');
            }
        },

        slugify(text) {
            return text.toLowerCase()
                .replace(/[а-яё]/gi, c => {
                    const map = {'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'yo','ж':'zh','з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'ts','ч':'ch','ш':'sh','щ':'shch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya'};
                    return map[c.toLowerCase()] || c;
                })
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
        },
    };
}
</script>
@endsection
