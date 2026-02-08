@props([
    'gradient' => null,
    'pressable' => false,
    'href' => null
])

@php
    $tag = $href ? 'a' : 'div';
    $baseClass = $gradient ? 'sm-card-gradient ' . $gradient : 'sm-card';
    $extraClass = $pressable ? ' native-pressable' : '';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'pwa-only ' . $baseClass . $extraClass]) }}
>
    {{ $slot }}
</{{ $tag }}>
