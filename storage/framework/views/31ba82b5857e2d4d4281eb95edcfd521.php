


<div x-data="actionSheet()"
     @action-sheet:open.window="open($event.detail)"
     x-cloak>

    
    <div x-show="isOpen"
         @click="close()"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] bg-black/40 backdrop-blur-sm"></div>

    
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-full"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-full"
         class="fixed bottom-0 left-0 right-0 z-[101] bg-white rounded-t-2xl shadow-2xl max-h-[80vh] overflow-y-auto"
         style="padding-bottom: env(safe-area-inset-bottom, 0px);">

        
        <div class="w-9 h-1 bg-gray-300 rounded-full mx-auto mt-3 mb-2"></div>

        
        <template x-if="title || message">
            <div class="px-6 py-4 text-center border-b border-gray-100">
                <h3 x-show="title" x-text="title" class="text-base font-semibold text-gray-900 mb-1"></h3>
                <p x-show="message" x-text="message" class="text-sm text-gray-600"></p>
            </div>
        </template>

        
        <div class="py-2">
            <template x-for="(action, index) in actions" :key="index">
                <button @click="handleAction(action)"
                        class="w-full flex items-center gap-3 px-6 py-4 text-left transition-colors active:bg-gray-50"
                        :class="action.destructive ? 'text-red-600' : 'text-gray-900'"
                        data-haptic="light">
                    
                    <template x-if="action.icon">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="action.icon"></path>
                        </svg>
                    </template>

                    
                    <span class="flex-1 text-base" x-text="action.title"></span>

                    
                    <template x-if="action.badge">
                        <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 rounded-full" x-text="action.badge"></span>
                    </template>
                </button>
            </template>
        </div>

        
        <div class="mt-2 p-2 bg-gray-50">
            <button @click="close()"
                    class="w-full py-3 text-base font-semibold text-blue-600 bg-white rounded-xl transition-colors active:bg-gray-100"
                    data-haptic="light"
                    x-text="cancelText">
            </button>
        </div>

    </div>

</div>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views/components/global-action-sheet.blade.php ENDPATH**/ ?>