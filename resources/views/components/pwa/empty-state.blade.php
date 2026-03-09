{{--
    PWA Empty State Component
    Native-style empty state placeholder

    @props
    - icon: string - SVG icon (optional)
    - title: string - Title text
    - description: string - Description text
--}}

@props([
    'icon' => null,
    'title' => '',
    'description' => '',
])

<div class="flex flex-col items-center justify-center py-16 px-6 text-center">
    {{-- Icon --}}
    @if($icon)
    <div class="w-20 h-20 mb-6 flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-full">
        <div class="w-10 h-10 text-gray-400 dark:text-gray-500">
            {!! $icon !!}
        </div>
    </div>
    @else
    <div class="w-20 h-20 mb-6 flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-full">
        <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
        </svg>
    </div>
    @endif

    {{-- Title --}}
    @if($title)
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
        {{ $title }}
    </h3>
    @endif

    {{-- Description --}}
    @if($description)
    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs mb-6">
        {{ $description }}
    </p>
    @endif

    {{-- Action Slot --}}
    {{ $slot }}
</div>
