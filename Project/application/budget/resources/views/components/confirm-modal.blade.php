@props([
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
    'icon' => null,
])

@php
    $httpMethod = strtoupper((string) $method);
    $formMethod = $httpMethod === 'GET' ? 'GET' : 'POST';
    $spoofMethod = in_array($httpMethod, ['GET', 'POST'], true) ? null : $httpMethod;
    $confirmClasses = $confirmVariant === 'primary'
        ? 'inline-flex items-center justify-center h-10 px-5 font-bold text-white text-xs uppercase rounded-lg bg-gradient-to-tl from-blue-600 to-green-500'
        : 'inline-flex items-center justify-center h-10 px-5 font-bold text-white text-xs uppercase rounded-lg bg-gradient-to-tl from-red-600 to-rose-500';
@endphp

<x-modal :name="$name" :show="$show" :maxWidth="$maxWidth" static>
    <div class="p-6">
        <h3 class="text-lg font-bold text-slate-800">{{ $title }}</h3>
        @if (!is_null($message) && strlen((string) $message) > 0)
            <p class="mt-1 text-sm text-slate-500">{{ $message }}</p>
        @endif
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="$dispatch('close-modal', '{{ $name }}')" class="inline-flex items-center justify-center h-10 px-4 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">{{ $cancelText }}</button>
            @if ($action)
                <form method="{{ $formMethod }}" action="{{ $action }}" class="inline">
                    @csrf
                    @if ($spoofMethod)
                        @method($spoofMethod)
                    @endif
                    <button type="submit" class="{{ $confirmClasses }}">{{ $confirmText }}</button>
                </form>
            @else
                <button type="button" @if ($confirmClick) @click="{{ $confirmClick }}" @endif class="{{ $confirmClasses }}">{{ $confirmText }}</button>
            @endif
        </div>
    </div>
</x-modal>
