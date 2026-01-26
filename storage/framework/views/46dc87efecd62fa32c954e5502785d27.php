


<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => '',
    'backUrl' => null,
    'backText' => 'Назад',
    'showProfile' => false,
    'showMenu' => false
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
    'title' => '',
    'backUrl' => null,
    'backText' => 'Назад',
    'showProfile' => false,
    'showMenu' => false
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<header class="pwa-only native-header">
    
    <div class="native-header-left">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($backUrl): ?>
            
            <a href="<?php echo e($backUrl); ?>"
               class="native-header-btn"
               onclick="if(window.haptic) window.haptic.light()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
        <?php elseif($showProfile): ?>
            
            <a href="/settings"
               class="native-header-avatar"
               onclick="if(window.haptic) window.haptic.light()">
                <span x-text="$store.auth.user?.name?.charAt(0) || 'U'"></span>
            </a>
        <?php elseif($showMenu): ?>
            
            <button @click="$store.ui.toggleSidebar()"
                    class="native-header-btn"
                    onclick="if(window.haptic) window.haptic.light()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        <?php else: ?>
            <div class="w-10"></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    
    <h1 class="native-header-title"><?php echo e($title); ?></h1>

    
    <div class="native-header-right">
        <?php echo e($slot); ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($slot->isEmpty()): ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showProfile): ?>
                <div class="w-10"></div>
            <?php else: ?>
                <div class="w-10"></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</header>

<style>
/* PWA Header Styles */
.pwa-mode .native-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: calc(44px + env(safe-area-inset-top, 0px));
    padding: 0 calc(20px + env(safe-area-inset-right, 0px)) 0 calc(20px + env(safe-area-inset-left, 0px));
    padding-top: env(safe-area-inset-top, 0px);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
}

.pwa-mode .native-header-left,
.pwa-mode .native-header-right {
    display: flex;
    align-items: center;
    min-width: 60px;
}

.pwa-mode .native-header-left {
    justify-content: flex-start;
}

.pwa-mode .native-header-right {
    justify-content: flex-end;
}

.pwa-mode .native-header-title {
    flex: 1;
    font-size: 17px;
    font-weight: 600;
    color: #000;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0 8px;
}

.pwa-mode .native-header-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    padding: 0;
    color: #007AFF;
    background: none;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
}

.pwa-mode .native-header-btn:active {
    opacity: 0.5;
    background: rgba(0, 122, 255, 0.1);
}

.pwa-mode .native-header-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #007AFF, #5856D6);
    border-radius: 50%;
    color: white;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    -webkit-tap-highlight-color: transparent;
}

.pwa-mode .native-header-avatar:active {
    opacity: 0.7;
    transform: scale(0.95);
}
</style>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\components\pwa-header.blade.php ENDPATH**/ ?>