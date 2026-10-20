@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'static' => false,
    'dialogClass' => '',
    // UI variant: 'plain' keeps current behavior, 'modern' adds a standardized header/body wrapper.
    'variant' => 'plain',
    // Header options (used when variant === 'modern')
    'title' => null,
    'description' => null,
    'titleId' => null,
    'iconClass' => null, // e.g. 'fas fa-layer-group'
])

@php
    $maxWidth = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        '6xl' => 'max-w-6xl',
    ][$maxWidth];
@endphp

<div x-data>
    <template x-teleport="body">
        <div {{ $attributes->merge(['class' => 'fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-[99999]']) }}
            x-data="{ show: @js($show), isStatic: @js($static) }"
            x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
            x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
            x-on:keydown.escape.window="null"
            x-cloak
            x-show="show">
            <div x-show="show" class="fixed inset-0">
                <div x-show="show" class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm" x-transition.opacity></div>
            </div>
            <div class="flex min-h-screen w-full items-center justify-center px-4 py-6">
                <div x-show="show"
                    class="w-full bg-white rounded-3xl overflow-visible shadow-xl transform transition-transform duration-200 ease-out {{ $maxWidth }} mx-auto {{ $dialogClass }}"
                    x-on:click.stop
                    x-transition>
                    @if ($variant === 'modern')
                        <div class="relative">
                            {{-- Header --}}
                            <div class="relative overflow-hidden rounded-t-3xl border-b border-slate-200/70 bg-gradient-to-br from-emerald-500/[0.10] via-blue-500/[0.08] to-white px-6 pb-4 pt-5">
                                <div class="pointer-events-none absolute inset-0">
                                    <div class="absolute -top-20 -left-16 h-60 w-60 rounded-full bg-[radial-gradient(circle,rgba(16,185,129,0.28),transparent_60%)] blur-[52px]"></div>
                                    <div class="absolute -bottom-24 -right-16 h-60 w-60 rounded-full bg-[radial-gradient(circle,rgba(59,130,246,0.22),transparent_62%)] blur-[54px]"></div>
                                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/80 to-transparent"></div>
                                </div>

                                <div class="relative flex items-start gap-3">
                                    @if (!is_null($iconClass) && strlen((string) $iconClass) > 0)
                                        <div class="mt-0.5 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl !bg-[linear-gradient(135deg,#059669,#2563eb)] text-white shadow-lg shadow-emerald-500/20 ring-1 ring-white/25">
                                            <i class="{{ $iconClass }} text-[14px]"></i>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <h3 @if ($titleId) id="{{ $titleId }}" @endif class="text-[18px] font-black tracking-tight text-slate-950 sm:text-[20px]">
                                            {{ $title }}
                                        </h3>
                                        @if (!is_null($description) && strlen((string) $description) > 0)
                                            <p class="mt-0.5 text-xs leading-relaxed text-slate-600">
                                                {{ $description }}
                                            </p>
                                        @endif
                                    </div>
                                    <button type="button" @click="$dispatch('close-modal', '{{ $name }}')"
                                        class="ml-auto inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-900/10 bg-white/70 text-slate-500 shadow-sm transition hover:bg-white hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300"
                                        aria-label="ปิดหน้าต่าง">
                                        <i class="fas fa-times text-[14px]"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="px-6 pb-6 pt-5">
                                {{ $slot }}
                            </div>
                        </div>
                    @else
                        {{ $slot }}
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
