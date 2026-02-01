@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'type' => 'text',
    'name' => null,
])

@php
$inputClasses = 'block w-full px-4 py-2 text-gray-900 placeholder-gray-400 bg-white border rounded-lg shadow-sm transition-colors focus:ring-2 focus:ring-offset-0';
$inputClasses .= $error 
    ? ' border-red-300 focus:border-red-500 focus:ring-red-500' 
    : ' border-gray-300 focus:border-blue-500 focus:ring-blue-500';
@endphp

<div>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
        </label>
    @endif
    
    <input 
        type="{{ $type }}"
        @if($name) name="{{ $name }}" id="{{ $name }}" @endif
        {{ $attributes->merge(['class' => $inputClasses]) }}
    >
    
    @if($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @elseif($hint)
        <p class="mt-1 text-sm text-gray-500">{{ $hint }}</p>
    @endif
</div>
