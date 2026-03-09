{{--
    PWA Bulk Toolbar Component
    Floating toolbar for bulk operations

    @props
    - countModel: string - Alpine.js variable for selected count
--}}

@props([
    'countModel' => 'selectedCount',
])

<div
    x-show="{{ $countModel }} > 0"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    class="fixed left-4 right-4 z-40 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700"
    style="bottom: calc(72px + env(safe-area-inset-bottom, 0px));"
>
    <div class="flex items-center justify-between px-4 py-3">
        {{-- Selection Info --}}
        <div class="flex items-center space-x-3">
            <div class="flex items-center space-x-2">
                <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    <span x-text="{{ $countModel }}"></span> {{ __('products.selected') ?? 'выбрано' }}
                </span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center space-x-2">
            {{ $slot }}

            {{-- Close Button --}}
            @if(isset($close))
                {{ $close }}
            @endif
        </div>
    </div>
</div>
