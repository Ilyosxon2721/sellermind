@props([
    'label' => null,
    'placeholder' => 'Выберите...',
    'error' => null,
    'options' => [],
    'name' => null,
])

@php
$selectClasses = 'block w-full px-4 py-2 text-gray-900 bg-white border rounded-lg shadow-sm transition-colors focus:ring-2 focus:ring-offset-0';
$selectClasses .= $error 
    ? ' border-red-300 focus:border-red-500 focus:ring-red-500' 
    : ' border-gray-300 focus:border-blue-500 focus:ring-blue-500';
@endphp

<div>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
        </label>
    @endif
    
    <select 
        @if($name) name="{{ $name }}" id="{{ $name }}" @endif
        {{ $attributes->merge(['class' => $selectClasses]) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        
        @foreach($options as $value => $text)
            <option value="{{ $value }}">{{ $text }}</option>
        @endforeach
        
        {{ $slot }}
    </select>
    
    @if($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
