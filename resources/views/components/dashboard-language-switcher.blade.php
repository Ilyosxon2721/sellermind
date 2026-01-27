<div class="relative" x-data="{ open: false }">
    <button 
        @click="open = !open" 
        class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition w-full text-left"
        :aria-expanded="open.toString()"
    >
        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
        </svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-200 flex-1">
            @if(auth()->user()->locale === 'uz') O'zbekcha
            @elseif(auth()->user()->locale === 'ru') Русский
            @else English
            @endif
        </span>
        <svg class="w-4 h-4 text-gray-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>
    
    <div 
        x-show="open" 
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute left-0 right-0 mt-2 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden z-50"
        style="display: none;"
        x-data="{
            async changeLocale(locale) {
                try {
                    const token = (() => { const t = localStorage.getItem('_x_auth_token'); return t ? JSON.parse(t) : null; })();
                    const response = await fetch('/api/me/locale', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                            ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                        },
                        credentials: 'include',
                        body: JSON.stringify({ locale })
                    });
                    
                    if (response.ok) {
                        // Reload page to apply new locale
                        window.location.reload();
                    } else {
                        const errorData = await response.json();
                        console.error('Failed to update locale:', errorData.message);
                    }
                } catch (error) {
                    console.error('Error updating locale:', error);
                }
            }
        }"
    >
        <button 
            @click="changeLocale('uz')" 
            class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition w-full {{ auth()->user()->locale === 'uz' ? 'bg-blue-50 dark:bg-blue-900/30' : '' }}"
        >
            <span class="text-2xl">🇺🇿</span>
            <span class="font-medium text-gray-700 dark:text-gray-200">O'zbekcha</span>
            @if(auth()->user()->locale === 'uz')
                <svg class="w-5 h-5 ml-auto text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
            @endif
        </button>
        <button 
            @click="changeLocale('ru')" 
            class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition w-full {{ auth()->user()->locale === 'ru' ? 'bg-blue-50 dark:bg-blue-900/30' : '' }}"
        >
            <span class="text-2xl">🇷🇺</span>
            <span class="font-medium text-gray-700 dark:text-gray-200">Русский</span>
            @if(auth()->user()->locale === 'ru')
                <svg class="w-5 h-5 ml-auto text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
            @endif
        </button>
        <button 
            @click="changeLocale('en')" 
            class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition w-full {{ auth()->user()->locale === 'en' ? 'bg-blue-50 dark:bg-blue-900/30' : '' }}"
        >
            <span class="text-2xl">🇬🇧</span>
            <span class="font-medium text-gray-700 dark:text-gray-200">English</span>
            @if(auth()->user()->locale === 'en')
                <svg class="w-5 h-5 ml-auto text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
            @endif
        </button>
    </div>
</div>
