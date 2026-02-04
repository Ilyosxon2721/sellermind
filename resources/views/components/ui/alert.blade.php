@props([
    'variant' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
$variants = [
    'success' => [
        'bg' => 'bg-green-50 border-green-200',
        'icon' => 'text-green-500',
        'title' => 'text-green-800',
        'text' => 'text-green-700',
    ],
    'warning' => [
        'bg' => 'bg-yellow-50 border-yellow-200',
        'icon' => 'text-yellow-500',
        'title' => 'text-yellow-800',
        'text' => 'text-yellow-700',
    ],
    'danger' => [
        'bg' => 'bg-red-50 border-red-200',
        'icon' => 'text-red-500',
        'title' => 'text-red-800',
        'text' => 'text-red-700',
    ],
    'info' => [
        'bg' => 'bg-blue-50 border-blue-200',
        'icon' => 'text-blue-500',
        'title' => 'text-blue-800',
        'text' => 'text-blue-700',
    ],
];

$v = $variants[$variant];
@endphp

<div 
    x-data="{ show: true }" 
    x-show="show"
    x-transition
    class="rounded-lg border p-4 {{ $v['bg'] }}"
>
    <div class="flex">
        <div class="flex-shrink-0">
            @if($variant === 'success')
                <svg class="h-5 w-5 {{ $v['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @elseif($variant === 'warning')
                <svg class="h-5 w-5 {{ $v['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            @elseif($variant === 'danger')
                <svg class="h-5 w-5 {{ $v['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @else
                <svg class="h-5 w-5 {{ $v['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @endif
        </div>
        <div class="ml-3 flex-1">
            @if($title)
                <p class="text-sm font-medium {{ $v['title'] }}">{{ $title }}</p>
            @endif
            <div class="text-sm {{ $v['text'] }} {{ $title ? 'mt-1' : '' }}">
                {{ $slot }}
            </div>
        </div>
        @if($dismissible)
            <button @click="show = false" class="ml-auto -mr-1 {{ $v['icon'] }} hover:opacity-75">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        @endif
    </div>
</div>
