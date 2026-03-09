@extends('layouts.app')

@section('content')
{{-- PWA Chat Page - AI Assistant --}}
<div
    class="pwa-only flex flex-col"
    x-data="chatPwaPage()"
    x-init="init()"
    style="height: 100vh; height: 100dvh; background: #f2f2f7;"
>
    {{-- Top Bar --}}
    <header class="chat-header flex items-center justify-between px-4 bg-white/95 backdrop-blur-xl border-b border-gray-200/50"
            style="height: calc(44px + env(safe-area-inset-top, 0px)); padding-top: env(safe-area-inset-top, 0px);">
        {{-- Back Button --}}
        <button
            @click="goBack()"
            class="flex items-center justify-center w-10 h-10 -ml-2 text-blue-600 rounded-lg active:bg-blue-50 transition-colors"
            onclick="if(window.haptic) window.haptic.light()"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>

        {{-- Title --}}
        <div class="flex-1 text-center">
            <h1 class="text-[17px] font-semibold text-gray-900">AI Ассистент</h1>
            <p x-show="isTyping" x-cloak class="text-[11px] text-gray-500">печатает...</p>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center space-x-1">
            {{-- Clear Chat --}}
            <button
                @click="confirmClearChat()"
                class="flex items-center justify-center w-10 h-10 text-gray-500 rounded-lg active:bg-gray-100 transition-colors"
                onclick="if(window.haptic) window.haptic.light()"
                title="Очистить чат"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>

            {{-- Settings --}}
            <button
                @click="showSettings = true"
                class="flex items-center justify-center w-10 h-10 text-gray-500 rounded-lg active:bg-gray-100 transition-colors"
                onclick="if(window.haptic) window.haptic.light()"
                title="Настройки"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </button>
        </div>
    </header>

    {{-- Messages Area --}}
    <div
        x-ref="messagesContainer"
        class="flex-1 overflow-y-auto px-4 py-4 space-y-3"
        style="padding-bottom: 20px;"
        @scroll="handleScroll()"
    >
        {{-- Welcome Message --}}
        <template x-if="messages.length === 0 && !isTyping">
            <div class="flex flex-col items-center justify-center h-full text-center px-8">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <span class="text-4xl">&#129302;</span>
                </div>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">SellerMind AI Ассистент</h2>
                <p class="text-sm text-gray-500 mb-6">
                    Я помогу вам с управлением товарами, анализом продаж и работой с маркетплейсами.
                </p>
            </div>
        </template>

        {{-- Messages List --}}
        <template x-for="(msg, index) in messages" :key="msg.id || index">
            <div
                :class="msg.type === 'user' ? 'flex justify-end' : 'flex justify-start'"
                x-show="msg.content"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform translate-y-2"
                x-transition:enter-end="opacity-1 transform translate-y-0"
            >
                <div class="flex flex-col" :class="msg.type === 'user' ? 'items-end' : 'items-start'">
                    {{-- Message Bubble --}}
                    <div
                        :class="msg.type === 'user'
                            ? 'bg-blue-600 text-white rounded-2xl rounded-br-md max-w-[80%]'
                            : 'bg-white border border-gray-200 text-gray-900 rounded-2xl rounded-bl-md max-w-[85%]'"
                        class="px-4 py-2.5 shadow-sm"
                    >
                        {{-- User message --}}
                        <template x-if="msg.type === 'user'">
                            <p class="text-[15px] leading-relaxed whitespace-pre-wrap break-words" x-text="msg.content"></p>
                        </template>

                        {{-- Assistant message with markdown --}}
                        <template x-if="msg.type === 'assistant'">
                            <div class="flex items-start space-x-2">
                                <span class="text-base flex-shrink-0 mt-0.5">&#129302;</span>
                                <div
                                    class="text-[15px] leading-relaxed prose prose-sm max-w-none"
                                    x-html="renderMarkdown(msg.content)"
                                ></div>
                            </div>
                        </template>

                        {{-- System message --}}
                        <template x-if="msg.type === 'system'">
                            <p class="text-[13px] leading-relaxed text-gray-600" x-text="msg.content"></p>
                        </template>
                    </div>

                    {{-- Timestamp and Status --}}
                    <div class="flex items-center space-x-1 mt-1 px-1" x-show="msg.timestamp">
                        <span class="text-[11px] text-gray-400" x-text="formatTime(msg.timestamp)"></span>
                        <template x-if="msg.type === 'user' && msg.status">
                            <span class="flex-shrink-0" x-html="getStatusIcon(msg.status)"></span>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        {{-- Typing Indicator --}}
        <div x-show="isTyping" x-cloak class="flex justify-start">
            <div class="bg-white border border-gray-200 rounded-2xl rounded-bl-md px-4 py-3 shadow-sm">
                <div class="flex items-center space-x-1">
                    <span class="typing-dot w-2 h-2 bg-gray-400 rounded-full"></span>
                    <span class="typing-dot w-2 h-2 bg-gray-400 rounded-full" style="animation-delay: 150ms;"></span>
                    <span class="typing-dot w-2 h-2 bg-gray-400 rounded-full" style="animation-delay: 300ms;"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Replies --}}
    <div
        x-show="showQuickReplies && messages.length === 0"
        x-cloak
        class="px-4 pb-2"
    >
        <div class="flex flex-wrap gap-2">
            <template x-for="reply in quickReplies" :key="reply">
                <button
                    @click="sendQuickReply(reply)"
                    class="px-4 py-2 bg-white border border-gray-200 rounded-full text-sm text-gray-700 font-medium active:bg-gray-100 transition-colors shadow-sm"
                    onclick="if(window.haptic) window.haptic.light()"
                    x-text="reply"
                ></button>
            </template>
        </div>
    </div>

    {{-- Input Area --}}
    <div class="bg-white border-t border-gray-200 px-4 py-3" style="padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px));">
        <div class="flex items-end space-x-2">
            {{-- Attach Button --}}
            <button
                @click="attachFile()"
                class="flex items-center justify-center w-10 h-10 text-gray-500 rounded-full active:bg-gray-100 transition-colors flex-shrink-0"
                onclick="if(window.haptic) window.haptic.light()"
                title="Прикрепить файл"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
            </button>

            {{-- Text Input --}}
            <div class="flex-1 relative">
                <textarea
                    x-ref="messageInput"
                    x-model="inputMessage"
                    @input="autoGrow($event.target)"
                    @keydown.enter.prevent="handleEnter($event)"
                    @focus="onInputFocus()"
                    @blur="onInputBlur()"
                    placeholder="Сообщение..."
                    rows="1"
                    class="w-full px-4 py-2.5 bg-gray-100 border-0 rounded-2xl text-[16px] leading-relaxed resize-none focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all"
                    style="max-height: 120px; min-height: 40px;"
                ></textarea>
            </div>

            {{-- Voice Input Button --}}
            <button
                x-show="!inputMessage.trim()"
                @click="startVoiceInput()"
                :class="isRecording ? 'bg-red-500 text-white' : 'text-gray-500'"
                class="flex items-center justify-center w-10 h-10 rounded-full active:bg-gray-100 transition-colors flex-shrink-0"
                onclick="if(window.haptic) window.haptic.light()"
                title="Голосовой ввод"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
            </button>

            {{-- Send Button --}}
            <button
                x-show="inputMessage.trim()"
                @click="sendMessage()"
                :disabled="!inputMessage.trim() || isTyping"
                class="flex items-center justify-center w-10 h-10 bg-blue-600 text-white rounded-full disabled:opacity-50 active:bg-blue-700 transition-colors flex-shrink-0"
                onclick="if(window.haptic) window.haptic.medium()"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Settings Sheet --}}
    <div
        x-show="showSettings"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click.self="showSettings = false"
        class="fixed inset-0 bg-black/40 z-50 flex items-end justify-center"
    >
        <div
            x-show="showSettings"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="transform translate-y-full"
            x-transition:enter-end="transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="transform translate-y-0"
            x-transition:leave-end="transform translate-y-full"
            class="w-full bg-white rounded-t-2xl"
            style="padding-bottom: env(safe-area-inset-bottom, 0px);"
        >
            {{-- Handle --}}
            <div class="flex justify-center pt-3 pb-2">
                <div class="w-10 h-1 bg-gray-300 rounded-full"></div>
            </div>

            {{-- Settings Content --}}
            <div class="px-4 pb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Настройки чата</h3>

                {{-- Model Selection --}}
                <div class="mb-4">
                    <label class="text-sm font-medium text-gray-700 mb-2 block">AI Модель</label>
                    <select
                        x-model="settings.model"
                        class="w-full px-4 py-3 bg-gray-100 border-0 rounded-xl text-[16px] focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    >
                        <option value="gpt-4">GPT-4 (Рекомендуется)</option>
                        <option value="gpt-3.5-turbo">GPT-3.5 Turbo (Быстрее)</option>
                        <option value="claude-3">Claude 3 Opus</option>
                    </select>
                </div>

                {{-- Language Selection --}}
                <div class="mb-6">
                    <label class="text-sm font-medium text-gray-700 mb-2 block">Язык ответов</label>
                    <select
                        x-model="settings.language"
                        class="w-full px-4 py-3 bg-gray-100 border-0 rounded-xl text-[16px] focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    >
                        <option value="ru">Русский</option>
                        <option value="uz">O'zbekcha</option>
                        <option value="en">English</option>
                    </select>
                </div>

                {{-- Close Button --}}
                <button
                    @click="showSettings = false"
                    class="w-full py-3 bg-blue-600 text-white font-semibold rounded-xl active:bg-blue-700 transition-colors"
                    onclick="if(window.haptic) window.haptic.medium()"
                >
                    Готово
                </button>
            </div>
        </div>
    </div>

    {{-- Clear Chat Confirmation --}}
    <div
        x-show="showClearConfirm"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click.self="showClearConfirm = false"
        class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center px-8"
    >
        <div
            x-show="showClearConfirm"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            class="w-full max-w-sm bg-white rounded-2xl overflow-hidden shadow-xl"
        >
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Очистить чат?</h3>
                <p class="text-sm text-gray-500 mb-6">Все сообщения будут удалены. Это действие нельзя отменить.</p>
            </div>
            <div class="flex border-t border-gray-200">
                <button
                    @click="showClearConfirm = false"
                    class="flex-1 py-4 text-blue-600 font-semibold active:bg-gray-100 transition-colors"
                    onclick="if(window.haptic) window.haptic.light()"
                >
                    Отмена
                </button>
                <div class="w-px bg-gray-200"></div>
                <button
                    @click="clearChat()"
                    class="flex-1 py-4 text-red-600 font-semibold active:bg-gray-100 transition-colors"
                    onclick="if(window.haptic) window.haptic.medium()"
                >
                    Очистить
                </button>
            </div>
        </div>
    </div>

    {{-- Hidden File Input --}}
    <input
        type="file"
        x-ref="fileInput"
        @change="handleFileSelect($event)"
        accept="image/*"
        class="hidden"
    />
</div>

{{-- Browser Mode Fallback --}}
<div class="browser-only flex h-screen bg-gray-50">
    <x-sidebar />
    <div class="flex-1 flex flex-col">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <h1 class="text-2xl font-bold text-gray-900">AI Ассистент</h1>
        </header>
        <main class="flex-1 overflow-y-auto p-6">
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <p class="text-gray-600 mb-4">Чат доступен в PWA режиме для лучшего опыта.</p>
                <a href="/chat" class="text-blue-600 hover:underline">Перейти к обычному чату</a>
            </div>
        </main>
    </div>
</div>

<script>
function chatPwaPage() {
    return {
        messages: [],
        inputMessage: '',
        isTyping: false,
        isRecording: false,
        showSettings: false,
        showClearConfirm: false,
        showQuickReplies: true,
        recognition: null,
        settings: {
            model: 'gpt-4',
            language: 'ru'
        },
        quickReplies: [
            'Цены на товары',
            'Остатки на складе',
            'Отчет по продажам',
            'Помощь с отзывами'
        ],

        init() {
            // Load messages from store or localStorage
            this.loadMessages();

            // Initialize Web Speech API
            this.initSpeechRecognition();

            // Scroll to bottom on init
            this.$nextTick(() => {
                this.scrollToBottom();
            });

            // Handle keyboard visibility
            this.handleKeyboard();
        },

        loadMessages() {
            // Try to load from Alpine store first
            if (this.$store.chat && this.$store.chat.messages) {
                this.messages = this.$store.chat.messages.map(m => ({
                    id: m.id,
                    type: m.sender === 'user' ? 'user' : 'assistant',
                    content: m.content,
                    timestamp: m.created_at || new Date().toISOString(),
                    status: 'read'
                }));
            } else {
                // Load from localStorage
                const saved = localStorage.getItem('chat_pwa_messages');
                if (saved) {
                    try {
                        this.messages = JSON.parse(saved);
                    } catch (e) {
                        this.messages = [];
                    }
                }
            }
        },

        saveMessages() {
            localStorage.setItem('chat_pwa_messages', JSON.stringify(this.messages));
        },

        async sendMessage() {
            if (!this.inputMessage.trim() || this.isTyping) return;

            const content = this.inputMessage.trim();
            this.inputMessage = '';
            this.showQuickReplies = false;

            // Reset textarea height
            if (this.$refs.messageInput) {
                this.$refs.messageInput.style.height = '40px';
            }

            // Add user message
            const userMessage = {
                id: Date.now(),
                type: 'user',
                content: content,
                timestamp: new Date().toISOString(),
                status: 'sent'
            };
            this.messages.push(userMessage);
            this.saveMessages();

            // Haptic feedback
            if (window.haptic) window.haptic.medium();

            // Scroll to bottom
            this.$nextTick(() => {
                this.scrollToBottom();
            });

            // Show typing indicator
            this.isTyping = true;

            // Update status to delivered
            setTimeout(() => {
                userMessage.status = 'delivered';
            }, 500);

            try {
                // Send to backend
                const response = await this.sendToAI(content);

                // Update status to read
                userMessage.status = 'read';

                // Add assistant response
                const assistantMessage = {
                    id: Date.now() + 1,
                    type: 'assistant',
                    content: response,
                    timestamp: new Date().toISOString()
                };
                this.messages.push(assistantMessage);
                this.saveMessages();

            } catch (error) {
                console.error('Failed to send message:', error);

                // Add error message
                this.messages.push({
                    id: Date.now() + 1,
                    type: 'system',
                    content: 'Не удалось получить ответ. Попробуйте позже.',
                    timestamp: new Date().toISOString()
                });

                if (window.toast) {
                    window.toast.error('Ошибка отправки сообщения');
                }
            } finally {
                this.isTyping = false;
                this.$nextTick(() => {
                    this.scrollToBottom();
                });
            }
        },

        async sendToAI(content) {
            // Use the chat store if available
            if (this.$store.chat) {
                await this.$store.chat.sendMessage(content);
                const lastMessage = this.$store.chat.messages[this.$store.chat.messages.length - 1];
                if (lastMessage && lastMessage.sender !== 'user') {
                    return lastMessage.content;
                }
            }

            // Fallback: direct API call
            const response = await fetch('/api/chat/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message: content,
                    model: this.settings.model,
                    language: this.settings.language
                })
            });

            if (!response.ok) {
                throw new Error('API request failed');
            }

            const data = await response.json();
            return data.response || data.message || 'Ответ получен';
        },

        sendQuickReply(reply) {
            this.inputMessage = reply;
            this.sendMessage();
        },

        handleEnter(event) {
            // Send on Enter, new line on Shift+Enter
            if (!event.shiftKey) {
                this.sendMessage();
            } else {
                // Allow new line
                const target = event.target;
                const start = target.selectionStart;
                const end = target.selectionEnd;
                this.inputMessage = this.inputMessage.substring(0, start) + '\n' + this.inputMessage.substring(end);
                this.$nextTick(() => {
                    target.selectionStart = target.selectionEnd = start + 1;
                    this.autoGrow(target);
                });
            }
        },

        autoGrow(element) {
            element.style.height = '40px';
            element.style.height = Math.min(element.scrollHeight, 120) + 'px';
        },

        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        handleScroll() {
            // Could implement lazy loading of older messages here
        },

        goBack() {
            if (window.haptic) window.haptic.light();
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/dashboard';
            }
        },

        confirmClearChat() {
            if (this.messages.length === 0) return;
            this.showClearConfirm = true;
        },

        clearChat() {
            this.messages = [];
            this.saveMessages();
            this.showClearConfirm = false;
            this.showQuickReplies = true;

            // Clear store too
            if (this.$store.chat) {
                this.$store.chat.messages = [];
            }

            if (window.haptic) window.haptic.medium();
        },

        attachFile() {
            this.$refs.fileInput.click();
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Handle file attachment
            // For now, just show a toast
            if (window.toast) {
                window.toast.info('Файл выбран: ' + file.name);
            }

            // Reset input
            event.target.value = '';
        },

        initSpeechRecognition() {
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                return;
            }

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            this.recognition.continuous = false;
            this.recognition.interimResults = true;
            this.recognition.lang = 'ru-RU';

            this.recognition.onresult = (event) => {
                const transcript = Array.from(event.results)
                    .map(result => result[0].transcript)
                    .join('');
                this.inputMessage = transcript;
            };

            this.recognition.onend = () => {
                this.isRecording = false;
                if (this.inputMessage.trim()) {
                    // Auto-send after voice input
                    // this.sendMessage();
                }
            };

            this.recognition.onerror = (event) => {
                this.isRecording = false;
                console.error('Speech recognition error:', event.error);
            };
        },

        startVoiceInput() {
            if (!this.recognition) {
                if (window.toast) {
                    window.toast.error('Голосовой ввод не поддерживается');
                }
                return;
            }

            if (this.isRecording) {
                this.recognition.stop();
                this.isRecording = false;
            } else {
                this.recognition.start();
                this.isRecording = true;
                if (window.haptic) window.haptic.medium();
            }
        },

        handleKeyboard() {
            // Handle virtual keyboard on iOS/Android
            if ('visualViewport' in window) {
                window.visualViewport.addEventListener('resize', () => {
                    // Scroll to bottom when keyboard appears
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                });
            }
        },

        onInputFocus() {
            // Ensure input is visible above keyboard
            setTimeout(() => {
                this.scrollToBottom();
            }, 300);
        },

        onInputBlur() {
            // Optional: do something when input loses focus
        },

        formatTime(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            return date.toLocaleTimeString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        getStatusIcon(status) {
            const icons = {
                'sent': '<svg class="w-3.5 h-3.5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                'delivered': '<svg class="w-3.5 h-3.5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7M5 7l4 4"/></svg>',
                'read': '<svg class="w-3.5 h-3.5 text-blue-400" fill="currentColor" viewBox="0 0 24 24"><path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12l-1.41 1.41L11.66 19l12-12-1.42-1.41zM.41 13.41L6 19l1.41-1.41L1.83 12 .41 13.41z"/></svg>'
            };
            return icons[status] || '';
        },

        renderMarkdown(text) {
            if (!text) return '';
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`(.*?)`/g, '<code class="bg-gray-100 px-1 py-0.5 rounded text-sm font-mono">$1</code>')
                .replace(/\n/g, '<br>');
        }
    };
}
</script>

<style>
/* Typing dots animation */
@keyframes typingBounce {
    0%, 60%, 100% {
        transform: translateY(0);
    }
    30% {
        transform: translateY(-4px);
    }
}

.typing-dot {
    animation: typingBounce 1s infinite;
}

/* Chat header blur */
.chat-header {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

/* Smooth scrolling for messages */
.pwa-mode [x-ref="messagesContainer"] {
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}

/* Hide scrollbar in messages */
.pwa-mode [x-ref="messagesContainer"]::-webkit-scrollbar {
    display: none;
}

.pwa-mode [x-ref="messagesContainer"] {
    scrollbar-width: none;
}

/* Message bubble animations */
.pwa-mode [class*="rounded-2xl"] {
    transition: transform 0.1s ease;
}

.pwa-mode [class*="rounded-2xl"]:active {
    transform: scale(0.98);
}

/* Prose styling for markdown */
.pwa-mode .prose strong {
    font-weight: 600;
}

.pwa-mode .prose em {
    font-style: italic;
}

.pwa-mode .prose code {
    background: rgba(0, 0, 0, 0.05);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 13px;
    font-family: ui-monospace, monospace;
}

/* Input focus state */
.pwa-mode textarea:focus {
    background: #f3f4f6;
}

/* Recording indicator pulse */
.pwa-mode .bg-red-500 {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}
</style>
@endsection
