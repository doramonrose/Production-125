@props([
    'text',
    'as' => 'span',
    'ariaLabel' => null,
])

@php
    $tag = (string) $as;
    $label = $ariaLabel ?? $text;
@endphp

<{{ $tag }} {{ $attributes->merge(['data-ui-tooltip' => $text, 'aria-label' => $label]) }}>
    {{ $slot }}
</{{ $tag }}>
