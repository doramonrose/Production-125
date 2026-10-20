@props([
    // Base id for hidden input. The component will also render:
    // - visible text input: `${id}-text`
    // - suggest container: `${id}-suggest`
    // - clear button: `${id}-clear` (when showClear=true)
    'id',
    'placeholder' => '',
    'options' => [],
    'valueKey' => '',
    'textKey' => '',
    // Optional key for runtime option updates (ex: 'plans', 'events')
    'optionsKey' => null,
    'showClear' => false,
    'clearTitle' => 'ล้างค่า',
])

<div
    class="relative isolate pbm-searchable-select-wrapper"
    data-pbm-searchable-select-wrapper="1"
    data-pbm-options-key="{{ $optionsKey ?? '' }}"
    data-pbm-value-key="{{ $valueKey }}"
    data-pbm-text-key="{{ $textKey }}">

    <input id="{{ $id }}-text" type="search" placeholder="{{ $placeholder }}"
        class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm {{ $showClear ? 'pr-10 pbm-hide-native-search-clear' : '' }} focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
        autocomplete="off" data-pbm-searchable-select-text />

    @if ($showClear)
        <style>
            /* Hide the browser-provided (native) clear button for search inputs,
               so we only show the component's clear button. */
            .pbm-hide-native-search-clear::-webkit-search-cancel-button,
            .pbm-hide-native-search-clear::-webkit-search-decoration {
                -webkit-appearance: none;
                appearance: none;
                display: none;
            }
        </style>
        <button type="button" id="{{ $id }}-clear"
            class="absolute right-2 top-1/2 hidden -translate-y-1/2 rounded-md p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
            data-ui-tooltip="{{ e($clearTitle) }}"
            aria-label="{{ e($clearTitle) }}"><i class="fas fa-times text-xs"></i></button>
    @endif

    <div id="{{ $id }}-suggest"
        class="absolute left-0 right-0 top-full !z-[9999999] hidden max-h-60 overflow-auto rounded-2xl border border-slate-200 bg-white py-1 shadow-2xl"
        data-pbm-searchable-select-suggest>
    </div>

    <input type="hidden" id="{{ $id }}" value="" data-pbm-searchable-select-hidden />

    <script type="application/json" class="pbm-searchable-select__options"
        data-options-key="{{ $optionsKey ?? '' }}">
        {!! json_encode($options) !!}
    </script>
</div>

@push('scripts')
    <script>
        (function() {
            if (window.PBMSearchableDropdown && window.PBMSearchableDropdown.__initDone) return;

            window.PBMSearchableDropdown = window.PBMSearchableDropdown || {};
            window.PBMSearchableDropdown.optionsByKey = window.PBMSearchableDropdown.optionsByKey || {};

            const escapeHtml = (unsafe) => String(unsafe ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            } [c]));

            const escapeAttr = (unsafe) => String(unsafe ?? '').replace(/"/g, '&quot;');

            const getOptionsFromDOM = (wrapper) => {
                const key = wrapper.dataset.pbmOptionsKey || '';
                const script = wrapper.querySelector('script.pbm-searchable-select__options');
                if (!script) return [];
                try {
                    const parsed = JSON.parse(script.textContent || '[]');
                    if (key) window.PBMSearchableDropdown.optionsByKey[key] = parsed;
                    return parsed;
                } catch (e) {
                    return [];
                }
            };

            const resolveOptions = (wrapper) => {
                const key = wrapper.dataset.pbmOptionsKey || '';
                if (key && window.PBMSearchableDropdown.optionsByKey[key]) {
                    return window.PBMSearchableDropdown.optionsByKey[key];
                }
                return getOptionsFromDOM(wrapper);
            };

            const updateClearVisibility = (textEl, clearBtn) => {
                if (!clearBtn) return;
                const has = (textEl.value || '').trim().length > 0;
                clearBtn.classList.toggle('hidden', !has);
            };

            const syncTextFromHidden = (wrapper, opts) => {
                const hiddenEl = wrapper.querySelector('[data-pbm-searchable-select-hidden]');
                const textEl = wrapper.querySelector('[data-pbm-searchable-select-text]');
                const valueKey = wrapper.dataset.pbmValueKey || '';
                const textKey = wrapper.dataset.pbmTextKey || '';

                const val = String(hiddenEl.value ?? '');
                if (!val) {
                    textEl.value = '';
                    updateClearVisibility(textEl, wrapper.querySelector('#' + hiddenEl.id + '-clear'));
                    return;
                }

                const found = (opts || []).find(item => String(item?.[valueKey]) === val);
                textEl.value = found ? (found?.[textKey] ?? '') : '';
                updateClearVisibility(textEl, wrapper.querySelector('#' + hiddenEl.id + '-clear'));
            };

            const renderSuggest = (wrapper, opts, term) => {
                const suggestEl = wrapper.querySelector('[data-pbm-searchable-select-suggest]');
                const textEl = wrapper.querySelector('[data-pbm-searchable-select-text]');
                const valueKey = wrapper.dataset.pbmValueKey || '';
                const textKey = wrapper.dataset.pbmTextKey || '';

                const t = (term ?? '').toLowerCase().trim();
                const items = (opts || []);
                const all = items.map(item => ({
                    value: item?.[valueKey] ?? '',
                    label: item?.[textKey] ?? '',
                }));

                const matches = !t ? all : all.filter(it => {
                    const label = String(it.label ?? '').toLowerCase();
                    const value = String(it.value ?? '').toLowerCase();
                    return label.includes(t) || value.includes(t);
                }).slice(0, 30);

                suggestEl.innerHTML = '';
                if (!matches.length) {
                    suggestEl.classList.remove('hidden');
                    suggestEl.innerHTML =
                        `<div class="px-3 py-2 text-sm text-slate-400">ไม่พบรายการ</div>`;
                    return;
                }

                suggestEl.classList.remove('hidden');
                suggestEl.innerHTML = matches.map(it => {
                    const value = it.value;
                    const label = it.label;
                    return `<button type="button" data-value="${escapeAttr(value)}" data-label="${escapeAttr(label)}"
                        class="w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">${escapeHtml(label)}</button>`;
                }).join('');
            };

            const bindInstance = (wrapper) => {
                if (wrapper.dataset.pbmInited === '1') return;
                wrapper.dataset.pbmInited = '1';

                const hiddenEl = wrapper.querySelector('[data-pbm-searchable-select-hidden]');
                const textEl = wrapper.querySelector('[data-pbm-searchable-select-text]');
                const suggestEl = wrapper.querySelector('[data-pbm-searchable-select-suggest]');
                const clearBtn = wrapper.querySelector('#' + hiddenEl.id + '-clear');

                const valueKey = wrapper.dataset.pbmValueKey || '';
                const textKey = wrapper.dataset.pbmTextKey || '';

                if (!valueKey || !textKey) {
                    // Misconfigured component: do nothing.
                    return;
                }

                let suppressBlur = false;
                const baseZ = Number(wrapper.style.zIndex || 0) || 0;
                const bringToFront = () => {
                    window.PBMSearchableDropdown.__zTop = Number(window.PBMSearchableDropdown.__zTop || 1000) + 1;
                    wrapper.style.zIndex = String(window.PBMSearchableDropdown.__zTop);
                };
                const resetZ = () => {
                    wrapper.style.zIndex = baseZ ? String(baseZ) : '';
                };

                const hideSuggest = () => {
                    suggestEl.classList.add('hidden');
                    suggestEl.innerHTML = '';
                    resetZ();
                };

                const showSuggestForCurrent = () => {
                    const opts = resolveOptions(wrapper);
                    bringToFront();
                    renderSuggest(wrapper, opts, textEl.value);
                };

                const showAll = () => {
                    const opts = resolveOptions(wrapper);
                    bringToFront();
                    renderSuggest(wrapper, opts, '');
                };

                const trySyncHiddenFromText = () => {
                    const opts = resolveOptions(wrapper);
                    const keyword = (textEl.value || '').trim();
                    if (!keyword) {
                        hiddenEl.value = '';
                        hideSuggest();
                        updateClearVisibility(textEl, clearBtn);
                        return;
                    }

                    const term = keyword.toLowerCase();
                    const found = (opts || []).find(item => {
                        const label = String(item?.[textKey] ?? '').trim().toLowerCase();
                        const code = String(item?.[valueKey] ?? '').trim().toLowerCase();
                        return label === term || code === term;
                    });

                    hiddenEl.value = found ? String(found?.[valueKey] ?? '') : '';
                    updateClearVisibility(textEl, clearBtn);
                };

                const dispatchValueChange = () => {
                    hiddenEl.dispatchEvent(new Event('input', { bubbles: true }));
                    hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                };

                // When the hidden value is updated programmatically (e.g. edit form),
                // keep the visible text input in sync.
                const syncFromHiddenNow = () => {
                    const opts = resolveOptions(wrapper);
                    syncTextFromHidden(wrapper, opts);
                };
                hiddenEl.addEventListener('input', syncFromHiddenNow);
                hiddenEl.addEventListener('change', syncFromHiddenNow);

                textEl.addEventListener('input', () => {
                    const before = hiddenEl.value;
                    trySyncHiddenFromText();
                    const after = hiddenEl.value;
                    if (before !== after) dispatchValueChange();

                    const keyword = (textEl.value || '').trim();
                    if (!keyword) {
                        hideSuggest();
                        return;
                    }
                    showSuggestForCurrent();
                });

                textEl.addEventListener('focus', () => {
                    updateClearVisibility(textEl, clearBtn);
                    if ((textEl.value || '').trim()) showSuggestForCurrent();
                    else showAll();
                });

                textEl.addEventListener('click', () => {
                    if ((textEl.value || '').trim()) showSuggestForCurrent();
                    else showAll();
                });

                textEl.addEventListener('blur', () => {
                    setTimeout(() => {
                        if (!suppressBlur) hideSuggest();
                        suppressBlur = false;
                    }, 160);
                });

                suggestEl.addEventListener('pointerdown', () => {
                    suppressBlur = true;
                });

                suggestEl.addEventListener('click', (e) => {
                    const btn = e.target.closest('button[data-value]');
                    if (!btn) return;
                    e.preventDefault();

                    const selectedValue = btn.getAttribute('data-value') || '';
                    const selectedLabel = btn.getAttribute('data-label') || '';

                    hiddenEl.value = selectedValue;
                    textEl.value = selectedLabel;
                    updateClearVisibility(textEl, clearBtn);
                    hideSuggest();
                    dispatchValueChange();
                });

                if (clearBtn) {
                    clearBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        hiddenEl.value = '';
                        textEl.value = '';
                        updateClearVisibility(textEl, clearBtn);
                        hideSuggest();
                        dispatchValueChange();
                        textEl.focus();
                    });
                }

                textEl.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        hideSuggest();
                        return;
                    }
                    if (e.key === 'Enter' && !suggestEl.classList.contains('hidden')) {
                        const first = suggestEl.querySelector('button[data-value]');
                        if (first) {
                            first.click();
                            e.preventDefault();
                        }
                    }
                });

                // Initial sync
                const opts = resolveOptions(wrapper);
                syncTextFromHidden(wrapper, opts);
                hideSuggest();
            };

            window.PBMSearchableDropdown.setOptions = (key, optionsArray) => {
                window.PBMSearchableDropdown.optionsByKey[key] = optionsArray || [];
                // Resync visible text for all instances under that key
                document.querySelectorAll('[data-pbm-searchable-select-wrapper][data-pbm-options-key="' + (key || '') + '"]').forEach(w => {
                    const hiddenEl = w.querySelector('[data-pbm-searchable-select-hidden]');
                    if (!hiddenEl || !hiddenEl.value) return;
                    const opts = window.PBMSearchableDropdown.optionsByKey[key] || [];
                    syncTextFromHidden(w, opts);
                });
            };

            const initAll = () => {
                const wrappers = document.querySelectorAll('[data-pbm-searchable-select-wrapper]');
                wrappers.forEach(bindInstance);
            };

            // Expose init for dynamic DOM (e.g. modals / teleported content)
            window.PBMSearchableDropdown.initAll = initAll;

            initAll();
            // When a modal opens, teleported content is inserted after this script ran.
            // Re-run init to bind newly added dropdowns.
            window.addEventListener('open-modal', () => {
                setTimeout(() => {
                    try { initAll(); } catch (e) {}
                }, 0);
            });
            window.PBMSearchableDropdown.__initDone = true;
        })();
    </script>
@endpush

