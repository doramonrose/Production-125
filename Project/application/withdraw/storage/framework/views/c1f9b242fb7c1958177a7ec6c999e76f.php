<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'type' => 'success',
    'message' => '',
    'timer' => 3000,
    'flash' => null,
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
    'type' => 'success',
    'message' => '',
    'timer' => 3000,
    'flash' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<style>.swal2-container{z-index:999999!important;}</style>

<?php
    $data = $flash ?? (strlen($message) > 0 ? ['type' => $type, 'message' => $message, 'timer' => $timer] : null);
?>

<?php if($data && isset($data['message'])): ?>
<?php
    $toastType = $data['type'] ?? 'success';
    $toastMessage = $data['message'];
    $toastTimer = (int) ($data['timer'] ?? 3000);
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.toast && window.toast[<?php echo e(Js::from($toastType)); ?>]) {
        window.toast[<?php echo e(Js::from($toastType)); ?>](<?php echo e(Js::from($toastMessage)); ?>, { timer: <?php echo e($toastTimer); ?> });
    }
});
</script>
<?php endif; ?>
<?php /**PATH /var/www/application/withdraw/resources/views/components/toast.blade.php ENDPATH**/ ?>