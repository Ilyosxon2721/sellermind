


<div x-data="{ show: false }"
     x-show="show"
     x-cloak
     @loading-start.window="show = true"
     @loading-end.window="show = false"
     class="fixed inset-0 z-[60] flex items-center justify-center bg-black/20 backdrop-blur-sm"
     style="display: none;">

    <div class="bg-white rounded-2xl p-6 shadow-2xl flex flex-col items-center space-y-4 mx-4">
        
        <div class="relative">
            <svg class="animate-spin h-12 w-12 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        
        <div class="text-center">
            <p class="text-lg font-semibold text-gray-900">Загрузка...</p>
            <p class="text-sm text-gray-500 mt-1">Пожалуйста, подождите</p>
        </div>
    </div>
</div>


<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views/components/loading-overlay.blade.php ENDPATH**/ ?>