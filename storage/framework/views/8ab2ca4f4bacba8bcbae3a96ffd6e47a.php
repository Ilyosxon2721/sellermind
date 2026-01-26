<?php $__env->startSection('content'); ?>


<div class="browser-only flex h-screen bg-gray-50" x-data="chatPage()" @click.away="showModelSelector = false">
    <?php if (isset($component)) { $__componentOriginal2880b66d47486b4bfeaf519598a469d6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2880b66d47486b4bfeaf519598a469d6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $attributes = $__attributesOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $component = $__componentOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__componentOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
    <div class="flex-1 flex flex-col">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <h1 class="text-2xl font-bold text-gray-900">Чат</h1>
        </header>
        <main class="flex-1 overflow-y-auto p-6">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600">Browser mode for Chat page. Use PWA for full experience.</p>
            </div>
        </main>
    </div>
</div>


<div class="pwa-only min-h-screen" x-data="chatPage()" style="background: #f2f2f7;">
    
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => $store.chat.currentDialog ? 'Чат' : 'Чаты']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($store.chat.currentDialog ? 'Чат' : 'Чаты')]); ?>
        <button x-show="$store.chat.currentDialog" @click="$store.chat.newChat(); isPrivateMode = false" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80)): ?>
<?php $attributes = $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80; ?>
<?php unset($__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80)): ?>
<?php $component = $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80; ?>
<?php unset($__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80); ?>
<?php endif; ?>

    
    <div x-show="!$store.chat.currentDialog" class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); min-height: 100vh;">
        <div class="px-4 py-4 space-y-3">
            
            <button @click="$store.chat.newChat(); isPrivateMode = false" class="native-card w-full native-pressable" onclick="if(window.haptic) window.haptic.light()">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div class="flex-1 text-left">
                        <p class="native-body font-semibold">Новый чат</p>
                        <p class="native-caption">Создать новый диалог с AI</p>
                    </div>
                </div>
            </button>

            
            <div class="native-list" x-show="$store.chat.dialogs.length > 0">
                <template x-for="dialog in $store.chat.dialogs" :key="dialog.id">
                    <div class="native-list-item native-list-item-chevron native-pressable"
                         @click="$store.chat.loadDialog(dialog.id); isPrivateMode = dialog.is_private"
                         onclick="if(window.haptic) window.haptic.light()">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <p class="native-body font-semibold truncate" x-text="dialog.title || 'Новый диалог'"></p>
                                <span x-show="dialog.is_private" class="text-xs">🔒</span>
                            </div>
                            <p class="native-caption mt-1" x-text="formatDate(dialog.updated_at)"></p>
                        </div>
                    </div>
                </template>
            </div>

            
            <div x-show="$store.chat.dialogs.length === 0" class="native-card text-center py-12 mt-8">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <p class="native-body text-gray-500 mb-2">Нет чатов</p>
                <p class="native-caption">Начните новый диалог с AI помощником</p>
            </div>
        </div>
    </div>

    
    <div x-show="$store.chat.currentDialog" class="flex flex-col" style="height: 100vh; padding-top: calc(44px + env(safe-area-inset-top, 0px));">
        
        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-4" style="padding-bottom: 80px;">
            <template x-for="message in $store.chat.messages" :key="message.id">
                <div :class="message.sender === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="message.sender === 'user' ? 'bg-blue-600 text-white' : 'bg-white'" class="max-w-[80%] rounded-2xl px-4 py-3 shadow-sm">
                        <p class="text-sm whitespace-pre-wrap" x-text="message.content"></p>
                    </div>
                </div>
            </template>

            
            <div x-show="$store.chat.loading" class="flex justify-start">
                <div class="bg-white rounded-2xl px-4 py-3 shadow-sm">
                    <div class="flex space-x-2">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="safe-area-bottom bg-white border-t border-gray-200 px-4 py-3">
            <div class="flex items-center space-x-2">
                <input type="text" x-model="message" @keydown.enter="sendMessage()" placeholder="Сообщение..." class="flex-1 native-input">
                <button @click="sendMessage()" :disabled="!message.trim() || $store.chat.loading" class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center disabled:opacity-50" onclick="if(window.haptic) window.haptic.medium()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function chatPage() {
    return {
        message: '',
        isPrivateMode: false,
        showModelSelector: false,

        async sendMessage() {
            if (!this.message.trim() || this.$store.chat.loading) return;

            const content = this.message;
            this.message = '';

            try {
                await this.$store.chat.sendMessage(content, {
                    is_private: this.isPrivateMode
                });
            } catch (error) {
                console.error('Failed to send message:', error);
                if (window.toast) {
                    window.toast.error('Не удалось отправить сообщение');
                }
            }
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);

            if (diff < 60) return 'Только что';
            if (diff < 3600) return Math.floor(diff / 60) + ' мин назад';
            if (diff < 86400) return Math.floor(diff / 3600) + ' ч назад';

            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short'
            });
        }
    };
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\pages\chat.blade.php ENDPATH**/ ?>