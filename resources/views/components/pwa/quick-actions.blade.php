@props([
    'actions' => null,
])

@php
$defaultActions = [
    [
        'icon' => 'arrow-path',
        'label' => __('pwa.quick_actions.sync'),
        'href' => '/marketplace/sync',
        'color' => 'blue',
        'badge' => null,
    ],
    [
        'icon' => 'currency-dollar',
        'label' => __('pwa.quick_actions.prices'),
        'href' => '/products/prices',
        'color' => 'green',
        'badge' => null,
    ],
    [
        'icon' => 'chat-bubble-left-right',
        'label' => __('pwa.quick_actions.reviews'),
        'href' => '/reviews',
        'color' => 'orange',
        'badge' => null,
    ],
    [
        'icon' => 'clipboard-document-list',
        'label' => __('pwa.quick_actions.tasks'),
        'href' => '/tasks',
        'color' => 'purple',
        'badge' => null,
    ],
];

$items = $actions ?? $defaultActions;
@endphp

<div {{ $attributes->merge(['class' => 'pwa-only']) }}>
    {{-- Grid container for quick actions --}}
    <div class="grid grid-cols-4 gap-3">
        @if($slot->isNotEmpty())
            {{ $slot }}
        @else
            @foreach($items as $action)
                <x-pwa.quick-action
                    :icon="$action['icon'] ?? 'arrow-path'"
                    :label="$action['label'] ?? ''"
                    :href="$action['href'] ?? null"
                    :color="$action['color'] ?? 'blue'"
                    :badge="$action['badge'] ?? null"
                    :disabled="$action['disabled'] ?? false"
                />
            @endforeach
        @endif
    </div>
</div>
