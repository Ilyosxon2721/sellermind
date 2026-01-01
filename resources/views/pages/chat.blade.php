@extends('layouts.app')

@section('content')
<div x-data="chatPage()"
     @click.away="showModelSelector = false"
     class="flex h-screen bg-gray-50">

    <!-- Sidebar with chat-specific content -->
    <x-sidebar>
        <!-- Chat Actions -->
        <div class="p-4 space-y-2">
            <button @click="$store.chat.newChat(); isPrivateMode = false"
                    class="w-full py-2.5 px-4 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Новый чат</span>
            </button>
            <button @click="$store.chat.newChat(); isPrivateMode = true"
                    class="w-full py-2 px-4 bg-gray-800 text-white rounded-lg font-medium hover:bg-gray-900 transition flex items-center justify-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span class="text-sm">Приватный чат</span>
            </button>
        </div>

        <!-- Dialogs List -->
        <div class="flex-1 overflow-y-auto px-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">История</p>
            <div class="space-y-1">
                <template x-for="dialog in $store.chat.dialogs" :key="dialog.id">
                    <button @click="$store.chat.loadDialog(dialog.id); isPrivateMode = dialog.is_private"
                            :class="{'bg-blue-50 text-blue-700': $store.chat.currentDialog?.id === dialog.id}"
                            class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition truncate flex items-center space-x-2">
                        <span x-show="dialog.is_private" class="flex-shrink-0">🔒</span>
                        <span x-text="dialog.title || 'Новый диалог'" class="truncate"></span>
                    </button>
                </template>
            </div>
        </div>
    </x-sidebar>

    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col">
        <!-- Private Mode Banner -->
        <div x-show="isPrivateMode" class="bg-gray-900 text-white px-4 py-2 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span class="text-sm font-medium">Приватный режим</span>
                <span class="text-xs text-gray-400">— история удалится при выходе</span>
            </div>
            <button @click="endPrivateChat()"
                    class="text-sm px-3 py-1 bg-red-600 hover:bg-red-700 rounded-lg transition">
                Завершить и удалить
            </button>
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto p-6">
            <!-- Empty State -->
            <div x-show="$store.chat.messages.length === 0" class="h-full flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Начните диалог</h2>
                <p class="text-gray-600 max-w-md">
                    Напишите вопрос или отправьте фото товара для создания карточки. Я помогу с маркетплейсами!
                </p>
            </div>

            <!-- Messages List -->
            <div class="max-w-3xl mx-auto space-y-4">
                <template x-for="msg in $store.chat.messages" :key="msg.id">
                    <div :class="msg.sender === 'user' ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="msg.sender === 'user' ? 'message-user' : 'message-assistant'"
                             class="message">
                            <div x-html="formatMessage(msg.content)"></div>
                        </div>
                    </div>
                </template>

                <!-- Loading -->
                <div x-show="$store.chat.loading" class="flex justify-start">
                    <div class="message message-assistant">
                        <div class="flex items-center space-x-2">
                            <span class="spinner"></span>
                            <span>Думаю...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="border-t border-gray-200 bg-white p-4">
            <div class="max-w-3xl mx-auto">
                <!-- AI Mode Selector -->
                <div class="mb-3">
                    <div class="flex items-center space-x-2 overflow-x-auto pb-2 scrollbar-hide">
                        <template x-for="mode in aiModes" :key="mode.id">
                            <button type="button"
                                    @click="aiMode = mode.id"
                                    :class="aiMode === mode.id
                                        ? 'bg-blue-100 text-blue-700 border-blue-300 ring-2 ring-blue-200'
                                        : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-gray-300'"
                                    class="flex items-center space-x-2 px-3 py-2 rounded-lg border transition-all whitespace-nowrap">
                                <span x-text="mode.icon"></span>
                                <span class="text-sm font-medium" x-text="mode.name"></span>
                            </button>
                        </template>
                    </div>
                    <!-- Mode Description -->
                    <p class="text-xs text-gray-500 mt-1" x-text="aiModes.find(m => m.id === aiMode)?.description"></p>
                </div>

                <form @submit.prevent="sendMessage" class="flex space-x-3">
                    <!-- Model Selector (Dropdown) -->
                    <div class="relative flex-shrink-0">
                        <button type="button"
                                @click="showModelSelector = !showModelSelector"
                                class="h-full flex items-center space-x-1.5 px-3 py-2 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 transition"
                                title="Выбрать модель ИИ">
                            <span x-text="aiModels.find(m => m.id === aiModel)?.icon"></span>
                            <span class="text-sm font-medium text-gray-700" x-text="aiModels.find(m => m.id === aiModel)?.name"></span>
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="showModelSelector"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             @click.away="showModelSelector = false"
                             class="absolute left-0 bottom-full mb-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50">
                            <template x-for="model in aiModels" :key="model.id">
                                <button type="button"
                                        @click="aiModel = model.id; showModelSelector = false"
                                        :class="aiModel === model.id ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'"
                                        class="w-full px-4 py-2 text-left flex items-center space-x-3">
                                    <span x-text="model.icon"></span>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium" x-text="model.name"></p>
                                        <p class="text-xs text-gray-500" x-text="model.description"></p>
                                    </div>
                                    <svg x-show="aiModel === model.id" class="w-4 h-4 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Image Model Selector (only in photos mode) -->
                    <div x-show="aiMode === 'photos'" class="relative flex-shrink-0">
                        <button type="button"
                                @click="showImageModelSelector = !showImageModelSelector"
                                class="h-full flex items-center space-x-1.5 px-3 py-2 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 transition"
                                title="Выбрать модель генерации">
                            <span x-text="imageModels.find(m => m.id === imageModel)?.icon"></span>
                            <span class="text-sm font-medium text-gray-700" x-text="imageModels.find(m => m.id === imageModel)?.name"></span>
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="showImageModelSelector"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             @click.away="showImageModelSelector = false"
                             class="absolute left-0 bottom-full mb-2 w-52 bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50">
                            <template x-for="model in imageModels" :key="model.id">
                                <button type="button"
                                        @click="imageModel = model.id; showImageModelSelector = false"
                                        :class="imageModel === model.id ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'"
                                        class="w-full px-4 py-2 text-left flex items-center space-x-3">
                                    <span x-text="model.icon"></span>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium" x-text="model.name"></p>
                                        <p class="text-xs text-gray-500" x-text="model.description"></p>
                                    </div>
                                    <svg x-show="imageModel === model.id" class="w-4 h-4 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Attach button -->
                    <button type="button"
                            class="p-3 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition"
                            title="Прикрепить файл">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                    </button>

                    <div class="flex-1 relative">
                        <input type="text"
                               x-model="message"
                               :disabled="$store.chat.loading"
                               :placeholder="aiMode === 'cards' ? 'Опишите товар или отправьте фото...'
                                           : aiMode === 'photos' ? 'Опишите какое фото нужно сгенерировать...'
                                           : aiMode === 'reviews' ? 'Вставьте отзыв для ответа...'
                                           : aiMode === 'analytics' ? 'Задайте вопрос об аналитике...'
                                           : aiMode === 'seo' ? 'Вставьте текст для оптимизации...'
                                           : 'Напишите сообщение или задайте вопрос...'"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>

                    <button type="submit"
                            :disabled="!message.trim() || $store.chat.loading"
                            class="px-5 py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </form>

                <p class="mt-2 text-xs text-center text-gray-500">
                    SellerMind AI может ошибаться. Проверяйте важную информацию.
                </p>
            </div>
        </div>
    </div>
</div>
<script>
function chatPage() {
    return {
        message: '',
        aiMode: new URLSearchParams(window.location.search).get('mode') || 'chat',
        aiModel: 'fast',
        imageModel: 'dalle3',
        isPrivateMode: false,
        aiModes: [
            { id: 'chat', name: 'Чат', icon: '💬', description: 'Общие вопросы и помощь' },
            { id: 'cards', name: 'Карточки', icon: '📦', description: 'Создание карточек товаров' },
            { id: 'photos', name: 'Фото', icon: '🖼️', description: 'Генерация промо-фото' },
            { id: 'reviews', name: 'Отзывы', icon: '⭐', description: 'Ответы на отзывы' },
            { id: 'analytics', name: 'Аналитика', icon: '📊', description: 'Анализ и рекомендации' },
            { id: 'seo', name: 'SEO', icon: '🔍', description: 'Оптимизация текстов' }
        ],
        aiModels: [
            { id: 'fast', name: 'Mind Lite', description: 'Быстрый и экономичный', icon: '⚡' },
            { id: 'smart', name: 'Mind Pro', description: 'Оптимальный баланс', icon: '🧠' },
            { id: 'premium', name: 'Mind Ultra', description: 'Максимальное качество', icon: '✨' }
        ],
        imageModels: [
            { id: 'dalle3', name: 'DALL-E 3', description: 'Креативный стиль', icon: '🎨' },
            { id: 'gpt4o', name: 'GPT-4o', description: 'Реалистичные фото', icon: '📷' }
        ],
        showModelSelector: false,
        showImageModelSelector: false,
        formatMessage(content) {
            if (!content) return '';
            // Parse markdown images with action buttons: ![alt](url)
            let formatted = content.replace(
                /!\[([^\]]*)\]\(([^)]+)\)/g,
                '<div class="relative inline-block group">' +
                '<img src="$2" alt="$1" class="max-w-full rounded-lg mt-2 mb-2 cursor-pointer hover:opacity-90 transition" style="max-height: 400px;" onclick="window.open(\'$2\', \'_blank\')">' +
                '<div class="absolute bottom-4 left-2 flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">' +
                '<button onclick="window.likeImage(\'$2\', this); event.stopPropagation();" class="p-2 bg-white/90 hover:bg-white rounded-full shadow-lg transition" title="Нравится">' +
                '<svg class="w-5 h-5 text-gray-600 hover:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path></svg>' +
                '</button>' +
                '<button onclick="window.dislikeImage(\'$2\', this); event.stopPropagation();" class="p-2 bg-white/90 hover:bg-white rounded-full shadow-lg transition" title="Не нравится">' +
                '<svg class="w-5 h-5 text-gray-600 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018c.163 0 .326.02.485.06L17 4m-7 10v2a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"></path></svg>' +
                '</button>' +
                '<button onclick="window.downloadImage(\'$2\'); event.stopPropagation();" class="p-2 bg-white/90 hover:bg-white rounded-full shadow-lg transition" title="Скачать">' +
                '<svg class="w-5 h-5 text-gray-600 hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>' +
                '</button>' +
                '</div>' +
                '</div>'
            );
            // Remove old download button syntax
            formatted = formatted.replace(/\[download:[^\]]+\]/g, '');
            // Parse bold: **text**
            formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            // Parse newlines
            formatted = formatted.replace(/\n/g, '<br>');
            return formatted;
        },
        async sendMessage() {
            if (!this.message.trim()) return;
            const authStore = this.$store.auth;
            const chatStore = this.$store.chat;
            if (!authStore.currentCompany && !chatStore.currentDialog) {
                alert('Пожалуйста, выберите компанию');
                return;
            }
            const msg = this.message;
            this.message = '';
            try {
                await chatStore.sendMessage(msg, {
                    mode: this.aiMode,
                    model: this.aiModel,
                    image_model: this.aiMode === 'photos' ? this.imageModel : null,
                    is_private: this.isPrivateMode
                });
            } catch (error) {
                this.message = msg; // Restore message on error
                console.error('Chat error:', error);
                alert(error.response?.data?.message || 'Ошибка отправки сообщения');
            }
        },
        async endPrivateChat() {
            if (!confirm('Вы уверены? История приватного чата будет удалена безвозвратно.')) {
                return;
            }

            const chatStore = this.$store.chat;
            if (chatStore.currentDialog?.id) {
                try {
                    await window.api.dialogs.hide(chatStore.currentDialog.id);
                    // Remove from local list
                    chatStore.dialogs = chatStore.dialogs.filter(d => d.id !== chatStore.currentDialog.id);
                    chatStore.newChat();
                    this.isPrivateMode = false;
                } catch (error) {
                    console.error('Failed to hide dialog:', error);
                    alert('Ошибка при удалении чата');
                }
            } else {
                // No dialog yet, just reset
                chatStore.newChat();
                this.isPrivateMode = false;
            }
        },
        async init() {
            // Wait for Alpine to fully initialize persisted data
            await this.$nextTick();

            const authStore = this.$store.auth;

            // Check both Alpine persist and localStorage directly
            const hasToken = authStore.token || localStorage.getItem('_x_auth_token');

            if (!hasToken) {
                window.location.href = '/login';
                return;
            }

            // Always load companies and dialogs on page init
            try {
                await authStore.loadCompanies();
            } catch (e) {
                console.error('Failed to load companies:', e);
                // If 401, the interceptor will redirect
            }
        }
    };
}

// Global functions for image actions
window.likeImage = function(imageUrl, button) {
    const svg = button.querySelector('svg');
    const isLiked = svg.getAttribute('fill') === 'currentColor';

    if (isLiked) {
        svg.setAttribute('fill', 'none');
        svg.classList.remove('text-green-500');
        svg.classList.add('text-gray-600');
    } else {
        svg.setAttribute('fill', 'currentColor');
        svg.classList.remove('text-gray-600');
        svg.classList.add('text-green-500');

        const container = button.closest('.flex');
        const dislikeBtn = container.querySelector('button:nth-child(2) svg');
        if (dislikeBtn) {
            dislikeBtn.setAttribute('fill', 'none');
            dislikeBtn.classList.remove('text-red-500');
            dislikeBtn.classList.add('text-gray-600');
        }
    }
    console.log('Liked image:', imageUrl);
};

window.dislikeImage = function(imageUrl, button) {
    const svg = button.querySelector('svg');
    const isDisliked = svg.getAttribute('fill') === 'currentColor';

    if (isDisliked) {
        svg.setAttribute('fill', 'none');
        svg.classList.remove('text-red-500');
        svg.classList.add('text-gray-600');
    } else {
        svg.setAttribute('fill', 'currentColor');
        svg.classList.remove('text-gray-600');
        svg.classList.add('text-red-500');

        const container = button.closest('.flex');
        const likeBtn = container.querySelector('button:nth-child(1) svg');
        if (likeBtn) {
            likeBtn.setAttribute('fill', 'none');
            likeBtn.classList.remove('text-green-500');
            likeBtn.classList.add('text-gray-600');
        }
    }
    console.log('Disliked image:', imageUrl);
};

window.downloadImage = async function(imageUrl) {
    try {
        const response = await fetch(imageUrl);
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'sellermind-image-' + Date.now() + '.png';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        console.log('Downloaded image:', imageUrl);
    } catch (error) {
        console.error('Download failed:', error);
        window.open(imageUrl, '_blank');
    }
};
</script>
@endsection
