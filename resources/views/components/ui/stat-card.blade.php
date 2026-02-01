@props([
    'title',
    'value',
    'change' => null,
    'changeType' => 'increase', // increase, decrease, neutral
    'icon' => null,
    'iconBg' => 'bg-blue-100',
    'iconColor' => 'text-blue-600',
])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center">
        @if($icon)
            <div class="flex-shrink-0 p-3 {{ $iconBg }} rounded-lg">
                {!! $icon !!}
            </div>
        @endif
        <div class="{{ $icon ? 'ml-4' : '' }}">
            <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
            <p class="text-2xl font-bold text-gray-900">{{ $value }}</p>
        </div>
    </div>
    
    @if($change !== null)
        <div class="mt-4 flex items-center text-sm">
            @if($changeType === 'increase')
                <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                <span class="text-green-600 font-medium">{{ $change }}</span>
            @elseif($changeType === 'decrease')
                <svg class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
                <span class="text-red-600 font-medium">{{ $change }}</span>
            @else
                <span class="text-gray-500 font-medium">{{ $change }}</span>
            @endif
            
            @isset($changeLabel)
                <span class="text-gray-500 ml-2">{{ $changeLabel }}</span>
            @endisset
        </div>
    @endif
</div>
