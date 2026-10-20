<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name',
    'show' => false,
    'maxWidth' => 'sm',
    'title' => __('ยืนยันทำรายการ?'),
    'message' => null,
    'action' => null,
    'method' => 'POST',
    'confirmText' => __('ยืนยัน'),
    'cancelText' => __('ยกเลิก'),
    'confirmVariant' => 'danger',
    'confirmClick' => null,
    'confirmId' => null,
    'icon' => null,
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
    'name',
    'show' => false,
    'maxWidth' => 'sm',
    'title' => __('ยืนยันทำรายการ?'),
    'message' => null,
    'action' => null,
    'method' => 'POST',
    'confirmText' => __('ยืนยัน'),
    'cancelText' => __('ยกเลิก'),
    'confirmVariant' => 'danger',
    'confirmClick' => null,
    'confirmId' => null,
    'icon' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $httpMethod = strtoupper((string) $method);
    $formMethod = $httpMethod === 'GET' ? 'GET' : 'POST';
    $spoofMethod = in_array($httpMethod, ['GET', 'POST'], true) ? null : $httpMethod;
    $confirmClasses = $confirmVariant === 'primary'
        ? 'inline-flex items-center justify-center h-10 px-5 font-bold text-white text-xs uppercase rounded-lg bg-gradient-to-tl from-blue-600 to-green-500'
        : 'inline-flex items-center justify-center h-10 px-5 font-bold text-white text-xs uppercase rounded-lg bg-gradient-to-tl from-red-600 to-rose-500';
?>

<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => $name,'show' => $show,'maxWidth' => $maxWidth,'static' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($name),'show' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($show),'maxWidth' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($maxWidth),'static' => true]); ?>
    <div class="p-6">
        <h3 class="text-lg font-bold text-slate-800"><?php echo e($title); ?></h3>
        <?php if(!is_null($message) && strlen((string) $message) > 0): ?>
            <p class="mt-1 text-sm text-slate-500"><?php echo e($message); ?></p>
        <?php endif; ?>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="$dispatch('close-modal', '<?php echo e($name); ?>')" class="inline-flex items-center justify-center h-10 px-4 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50"><?php echo e($cancelText); ?></button>
            <?php if($action): ?>
                <form method="<?php echo e($formMethod); ?>" action="<?php echo e($action); ?>" class="inline">
                    <?php echo csrf_field(); ?>
                    <?php if($spoofMethod): ?>
                        <?php echo method_field($spoofMethod); ?>
                    <?php endif; ?>
                    <button type="submit" class="<?php echo e($confirmClasses); ?>"><?php echo e($confirmText); ?></button>
                </form>
            <?php else: ?>
                <button
                    type="button"
                    <?php if($confirmId): ?> id="<?php echo e($confirmId); ?>" <?php endif; ?>
                    <?php if($confirmClick): ?> @click="<?php echo e($confirmClick); ?>" <?php endif; ?>
                    class="<?php echo e($confirmClasses); ?>"
                ><?php echo e($confirmText); ?></button>
            <?php endif; ?>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php /**PATH /var/www/application/withdraw/resources/views/components/confirm-modal.blade.php ENDPATH**/ ?>