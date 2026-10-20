<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    // Base input id (required)
    'id',
    'name' => null,
    'placeholder' => 'เลือกวันที่',
    // Value should be ISO date string: YYYY-MM-DD
    'value' => null,
    // Visual options
    'withIcon' => true,
    'inputClass' => '',
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
    // Base input id (required)
    'id',
    'name' => null,
    'placeholder' => 'เลือกวันที่',
    // Value should be ISO date string: YYYY-MM-DD
    'value' => null,
    // Visual options
    'withIcon' => true,
    'inputClass' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="relative pbm-date-picker" data-pbm-date-picker="1">
    <?php if($withIcon): ?>
        <span class="pointer-events-none absolute inset-y-0 left-3 inline-flex items-center text-slate-400">
            <i class="far fa-calendar-alt text-[13px]"></i>
        </span>
    <?php endif; ?>

    <input
        id="<?php echo e($id); ?>"
        <?php if($name): ?> name="<?php echo e($name); ?>" <?php endif; ?>
        type="text"
        inputmode="numeric"
        autocomplete="off"
        placeholder="<?php echo e($placeholder); ?>"
        value="<?php echo e($value ?? ''); ?>"
        class="w-full rounded-2xl border border-slate-300/90 bg-white <?php echo e($withIcon ? 'pl-10' : 'pl-3.5'); ?> pr-3.5 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 <?php echo e($inputClass); ?>"
        data-pbm-date-picker-input
    />
</div>

<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
    <script>
        (function() {
            window.PBMDatePicker = window.PBMDatePicker || {};
            if (window.PBMDatePicker.__loaded) return;
            window.PBMDatePicker.__loaded = true;

            const initInput = (input) => {
                if (!input || input._flatpickr) return;
                if (!window.flatpickr) return;

                const toThaiBuddhistText = (fp, dateObj) => {
                    if (!dateObj || !(dateObj instanceof Date) || isNaN(dateObj.getTime())) return '';
                    const day = dateObj.getDate();
                    const monthName = fp?.l10n?.months?.longhand?.[dateObj.getMonth()] || '';
                    const yearBE = dateObj.getFullYear() + 543;
                    return `${day} ${monthName} ${yearBE}`;
                };

                const applyAltBuddhistYear = (instance) => {
                    if (!instance?.altInput) return;
                    const d = instance.selectedDates?.[0];
                    instance.altInput.value = toThaiBuddhistText(instance, d);
                };

                window.flatpickr(input, {
                    dateFormat: 'Y-m-d', // value submitted/stored (ค.ศ.)
                    altInput: true,
                    altFormat: 'j F Y', // will be overridden for พ.ศ.
                    // Avoid manual typing (prevents mis-parsing of พ.ศ.)
                    allowInput: false,
                    clickOpens: true,
                    disableMobile: true,
                    appendTo: document.body,
                    // Default should be empty unless a value is provided.
                    defaultDate: (input.value && String(input.value).trim()) ? input.value : null,
                    locale: (window.flatpickr?.l10ns?.th) ? window.flatpickr.l10ns.th : 'default',
                    onReady: (selectedDates, dateStr, instance) => {
                        // Make the visible input match our Tailwind styles.
                        if (instance?.altInput) {
                            instance.altInput.className = input.className;
                            instance.altInput.setAttribute('placeholder', input.getAttribute('placeholder') || '');
                            instance.altInput.readOnly = true;
                        }
                        if (instance?.calendarContainer) {
                            instance.calendarContainer.style.zIndex = '100000';
                        }
                        applyAltBuddhistYear(instance);
                    },
                    onValueUpdate: (selectedDates, dateStr, instance) => applyAltBuddhistYear(instance),
                    onOpen: (selectedDates, dateStr, instance) => applyAltBuddhistYear(instance),
                    onClose: (selectedDates, dateStr, instance) => applyAltBuddhistYear(instance),
                    onChange: () => {
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    },
                });
            };

            const initAll = () => {
                document.querySelectorAll('[data-pbm-date-picker-input]').forEach(initInput);
            };

            window.PBMDatePicker.initAll = initAll;

            initAll();
            window.addEventListener('open-modal', () => setTimeout(initAll, 0));
        })();
    </script>
<?php $__env->stopPush(); ?>

<?php /**PATH /var/www/application/withdraw/resources/views/components/date-picker.blade.php ENDPATH**/ ?>