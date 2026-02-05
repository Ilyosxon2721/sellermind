@props([
    'name' => null,
    'label' => null,
    'checked' => false,
    'error' => null,
])

<div>
    <label class="inline-flex items-center cursor-pointer">
        <input 
            type="checkbox"
            @if($name) name="{{ $name }}" @endif
            {{ $checked ? 'checked' : '' }}
            {{ $attributes->merge(['class' => 'w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2']) }}
        >
        @if($label)
            <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
        @endif
    </label>
    
    @if($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
