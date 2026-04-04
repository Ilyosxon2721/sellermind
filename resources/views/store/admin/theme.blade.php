@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }"
         x-data="themeEditor({{ $storeId ?? 'null' }})">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="/my-store" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Дизайн магазина</h1>
                        <p class="text-sm text-gray-500">Настройка внешнего вида и темы</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <a :href="storeUrl" target="_blank"
                       class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span>Превью</span>
                    </a>
                    <button @click="saveTheme()"
                            :disabled="saving"
                            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl transition-all shadow-lg shadow-blue-500/25 flex items-center space-x-2 disabled:opacity-50">
                        <svg x-show="!saving" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <svg x-show="saving" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="saving ? 'Сохранение...' : 'Сохранить'"></span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            {{-- Загрузка --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-20">
                    <svg class="animate-spin w-8 h-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    <span class="ml-3 text-gray-500">Загрузка темы...</span>
                </div>
            </template>

            <template x-if="!loading">
                <div class="space-y-6">
                    {{-- Шаблон --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Шаблон</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <template x-for="tpl in templates" :key="tpl.id">
                                <button @click="theme.template = tpl.id"
                                        class="relative rounded-xl border-2 p-4 text-center transition-all hover:shadow-md"
                                        :class="theme.template === tpl.id ? 'border-blue-500 bg-blue-50 shadow-md' : 'border-gray-200 bg-white'">
                                    <div class="w-12 h-12 mx-auto mb-2 rounded-lg flex items-center justify-center"
                                         :class="tpl.bgClass">
                                        <span class="text-2xl" x-text="tpl.icon"></span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900" x-text="tpl.name"></p>
                                    <p class="text-xs text-gray-500" x-text="tpl.desc"></p>
                                    <div x-show="theme.template === tpl.id"
                                         class="absolute top-2 right-2 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Цвета --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Цвета</h2>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Основной</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" x-model="theme.colors.primary" class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer">
                                    <input type="text" x-model="theme.colors.primary" class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Вторичный</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" x-model="theme.colors.secondary" class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer">
                                    <input type="text" x-model="theme.colors.secondary" class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Акцент</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" x-model="theme.colors.accent" class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer">
                                    <input type="text" x-model="theme.colors.accent" class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Фон</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" x-model="theme.colors.background" class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer">
                                    <input type="text" x-model="theme.colors.background" class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Текст</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" x-model="theme.colors.text" class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer">
                                    <input type="text" x-model="theme.colors.text" class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Шрифты --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Шрифты</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Заголовки</label>
                                <select x-model="theme.heading_font" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <template x-for="f in fonts" :key="f">
                                        <option :value="f" x-text="f" :style="'font-family:' + f"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Текст</label>
                                <select x-model="theme.body_font" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <template x-for="f in fonts" :key="f">
                                        <option :value="f" x-text="f" :style="'font-family:' + f"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Шапка --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Шапка сайта</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Стиль шапки</label>
                                <select x-model="theme.header.style" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="default">По умолчанию</option>
                                    <option value="centered">Центрированный</option>
                                    <option value="minimal">Минимальный</option>
                                </select>
                            </div>
                            <div class="flex items-end space-x-6">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" x-model="theme.header.show_search" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Поиск</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" x-model="theme.header.show_cart" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Корзина</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" x-model="theme.header.show_phone" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Телефон</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Hero секция --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Hero секция</h2>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" x-model="theme.hero.enabled" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700">Включена</span>
                            </label>
                        </div>
                        <div x-show="theme.hero.enabled" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Заголовок</label>
                                <input type="text" x-model="theme.hero.title" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Добро пожаловать!">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Подзаголовок</label>
                                <input type="text" x-model="theme.hero.subtitle" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Лучшие товары по выгодным ценам">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">URL изображения</label>
                                <input type="url" x-model="theme.hero.image" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="https://...">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Текст кнопки</label>
                                <input type="text" x-model="theme.hero.button_text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Перейти в каталог">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ссылка кнопки</label>
                                <input type="url" x-model="theme.hero.button_url" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="/catalog">
                            </div>
                        </div>
                    </div>

                    {{-- Подвал --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Подвал</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Стиль подвала</label>
                                <select x-model="theme.footer.style" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="default">По умолчанию</option>
                                    <option value="minimal">Минимальный</option>
                                    <option value="extended">Расширенный</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Текст подвала</label>
                                <input type="text" x-model="theme.footer.text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Все права защищены">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Цвет фона</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" x-model="theme.footer.bg_color" class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer">
                                    <input type="text" x-model="theme.footer.bg_color" class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Цвет текста</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" x-model="theme.footer.text_color" class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer">
                                    <input type="text" x-model="theme.footer.text_color" class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Настройки каталога --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Каталог</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Товаров на странице</label>
                                <select x-model="theme.products_per_page" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="6">6</option>
                                    <option value="12">12</option>
                                    <option value="24">24</option>
                                    <option value="48">48</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Стиль карточки товара</label>
                                <select x-model="theme.product_card_style" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="default">По умолчанию</option>
                                    <option value="minimal">Минимальный</option>
                                    <option value="detailed">Подробный</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Кастомный CSS --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Кастомный CSS</h2>
                        <textarea x-model="theme.custom_css" rows="8"
                                  class="w-full border border-gray-300 rounded-xl px-4 py-3 font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="/* Ваш CSS код */"></textarea>
                    </div>
                </div>
            </template>
        </main>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function themeEditor(storeId) {
    return {
        storeId,
        loading: true,
        saving: false,
        storeUrl: '',
        fonts: ['Inter', 'Roboto', 'Open Sans', 'Montserrat', 'Lora'],
        templates: [
            { id: 'default', name: 'Стандарт', desc: 'Универсальный', icon: '🏪', bgClass: 'bg-blue-100' },
            { id: 'minimal', name: 'Минимализм', desc: 'Чистый стиль', icon: '✨', bgClass: 'bg-gray-100' },
            { id: 'boutique', name: 'Бутик', desc: 'Элегантный', icon: '👗', bgClass: 'bg-pink-100' },
            { id: 'tech', name: 'Техно', desc: 'Технологичный', icon: '💻', bgClass: 'bg-indigo-100' },
            { id: 'grocery', name: 'Продукты', desc: 'Яркий', icon: '🛒', bgClass: 'bg-green-100' },
            { id: 'marketplace', name: 'Маркетплейс', desc: 'Как WB/Uzum', icon: '🛍️', bgClass: 'bg-purple-100' },
        ],
        theme: {
            template: 'default',
            colors: {
                primary: '#2563eb',
                secondary: '#4f46e5',
                accent: '#f59e0b',
                background: '#ffffff',
                text: '#111827',
            },
            heading_font: 'Inter',
            body_font: 'Inter',
            header: {
                style: 'default',
                show_search: true,
                show_cart: true,
                show_phone: true,
            },
            hero: {
                enabled: true,
                title: '',
                subtitle: '',
                image: '',
                button_text: '',
                button_url: '',
            },
            footer: {
                style: 'default',
                text: '',
                bg_color: '#1f2937',
                text_color: '#ffffff',
            },
            products_per_page: '12',
            product_card_style: 'default',
            custom_css: '',
        },

        init() {
            this.loadTheme();
        },

        async loadTheme() {
            this.loading = true;
            try {
                const res = await window.api.get(`/store/stores/${this.storeId}/theme`);
                const d = res.data.data ?? res.data;
                if (d) {
                    this.theme.template = d.template || 'default';
                    this.theme.colors = {
                        primary: d.primary_color || '#2563eb',
                        secondary: d.secondary_color || '#4f46e5',
                        accent: d.accent_color || '#f59e0b',
                        background: d.background_color || '#ffffff',
                        text: d.text_color || '#111827',
                    };
                    this.theme.heading_font = d.heading_font || 'Inter';
                    this.theme.body_font = d.body_font || 'Inter';
                    this.theme.header = {
                        style: d.header_style || 'default',
                        show_search: d.show_search ?? true,
                        show_cart: d.show_cart ?? true,
                        show_phone: d.show_phone ?? true,
                    };
                    this.theme.hero = {
                        enabled: d.hero_enabled ?? true,
                        title: d.hero_title || '',
                        subtitle: d.hero_subtitle || '',
                        image: d.hero_image || '',
                        button_text: d.hero_button_text || '',
                        button_url: d.hero_button_url || '',
                    };
                    this.theme.footer = {
                        style: d.footer_style || 'default',
                        text: d.footer_text || '',
                        bg_color: d.footer_bg_color || '#1f2937',
                        text_color: d.footer_text_color || '#ffffff',
                    };
                    this.theme.products_per_page = String(d.products_per_page || 12);
                    this.theme.product_card_style = d.product_card_style || 'default';
                    this.theme.custom_css = d.custom_css || '';
                    this.storeUrl = d.store_url || '#';
                }
            } catch (e) {
                window.toast?.error('Не удалось загрузить тему');
            } finally {
                this.loading = false;
            }
        },

        async saveTheme() {
            this.saving = true;
            try {
                const payload = {
                    template: this.theme.template,
                    primary_color: this.theme.colors.primary,
                    secondary_color: this.theme.colors.secondary,
                    accent_color: this.theme.colors.accent,
                    background_color: this.theme.colors.background,
                    text_color: this.theme.colors.text,
                    heading_font: this.theme.heading_font,
                    body_font: this.theme.body_font,
                    header_style: this.theme.header.style,
                    show_search: this.theme.header.show_search,
                    show_cart: this.theme.header.show_cart,
                    show_phone: this.theme.header.show_phone,
                    hero_enabled: this.theme.hero.enabled,
                    hero_title: this.theme.hero.title,
                    hero_subtitle: this.theme.hero.subtitle,
                    hero_image: this.theme.hero.image,
                    hero_button_text: this.theme.hero.button_text,
                    hero_button_url: this.theme.hero.button_url,
                    products_per_page: parseInt(this.theme.products_per_page) || 12,
                    product_card_style: this.theme.product_card_style,
                    footer_style: this.theme.footer.style,
                    footer_bg_color: this.theme.footer.bg_color,
                    footer_text_color: this.theme.footer.text_color,
                    footer_text: this.theme.footer.text,
                    custom_css: this.theme.custom_css,
                };
                await window.api.put(`/store/stores/${this.storeId}/theme`, payload);
                window.toast?.success('Тема сохранена');
            } catch (e) {
                const msg = e.response?.data?.message || 'Ошибка при сохранении темы';
                window.toast?.error(msg);
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endsection
