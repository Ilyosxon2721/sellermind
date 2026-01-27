<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => null,
    'text' => null,
    'url' => null,
    'label' => 'Поделиться',
    'icon' => true,
    'variant' => 'default', // default, primary, ghost, icon-only
    'size' => 'md', // sm, md, lg
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'title' => null,
    'text' => null,
    'url' => null,
    'label' => 'Поделиться',
    'icon' => true,
    'variant' => 'default', // default, primary, ghost, icon-only
    'size' => 'md', // sm, md, lg
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$baseClasses = 'inline-flex items-center justify-center gap-2 font-medium transition-colors rounded-xl';

$variantClasses = [
    'default' => 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 active:bg-gray-100',
    'primary' => 'bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800',
    'ghost' => 'bg-transparent text-gray-600 hover:bg-gray-100 active:bg-gray-200',
    'icon-only' => 'bg-transparent text-gray-600 hover:bg-gray-100 active:bg-gray-200 rounded-full',
];

$sizeClasses = [
    'sm' => 'px-3 py-2 text-sm',
    'md' => 'px-4 py-2.5 text-base',
    'lg' => 'px-6 py-3 text-lg',
];

$iconSizeClasses = [
    'sm' => 'w-4 h-4',
    'md' => 'w-5 h-5',
    'lg' => 'w-6 h-6',
];

$classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['default']);

if ($variant !== 'icon-only') {
    $classes .= ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
} else {
    $classes .= ' p-2';
}

$iconSize = $iconSizeClasses[$size] ?? $iconSizeClasses['md'];

$shareData = json_encode([
    'title' => $title,
    'text' => $text,
    'url' => $url ?? url()->current(),
]);
?>

<button
    <?php echo e($attributes->merge(['class' => $classes])); ?>

    x-data
    @click="$share.share(<?php echo e($shareData); ?>)"
    data-haptic="light"
    type="button">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($icon): ?>
        <svg class="<?php echo e($iconSize); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
        </svg>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($variant !== 'icon-only'): ?>
        <span><?php echo e($label); ?></span>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</button>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\components\share-button.blade.php ENDPATH**/ ?>