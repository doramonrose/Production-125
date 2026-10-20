@props([
    // Base input id (required)
    'id',
    'name' => null,
    'placeholder' => 'เลือกวันที่',
    // Value should be ISO date string: YYYY-MM-DD
    'value' => null,
    // Visual options
    'withIcon' => true,
    'inputClass' => '',
])

<div class="relative pbm-date-picker" data-pbm-date-picker="1">
    @if ($withIcon)
        <span class="pointer-events-none absolute inset-y-0 left-3 inline-flex items-center text-slate-400">
            <i class="far fa-calendar-alt text-[13px]"></i>
        </span>
    @endif

    <input
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        type="text"
        inputmode="numeric"
        autocomplete="off"
        placeholder="{{ $placeholder }}"
        value="{{ $value ?? '' }}"
        class="w-full rounded-2xl border border-slate-300/90 bg-white {{ $withIcon ? 'pl-10' : 'pl-3.5' }} pr-3.5 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 {{ $inputClass }}"
        data-pbm-date-picker-input
    />
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
@endpush

@push('scripts')
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
                    // Default should be empty unless a value is provided.
                    defaultDate: (function () {
                        const raw = String(input.value || '').trim();
                        const m = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
                        if (m && Number(m[1]) >= 2400) {
                            return `${String(Number(m[1]) - 543).padStart(4, '0')}-${m[2]}-${m[3]}`;
                        }
                        return raw ? raw : null;
                    })(),
                    locale: (window.flatpickr?.l10ns?.th) ? window.flatpickr.l10ns.th : 'default',
                    onReady: (selectedDates, dateStr, instance) => {
                        // Make the visible input match our Tailwind styles.
                        if (instance?.altInput) {
                            instance.altInput.className = input.className;
                            instance.altInput.setAttribute('placeholder', input.getAttribute('placeholder') || '');
                            instance.altInput.readOnly = true;
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
@endpush

