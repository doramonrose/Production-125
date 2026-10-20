@props([
    'type' => 'success',
    'message' => '',
    'timer' => 3000,
    'flash' => null,
])

<style>.swal2-container{z-index:999999!important;}</style>

@php
    $data = $flash ?? (strlen($message) > 0 ? ['type' => $type, 'message' => $message, 'timer' => $timer] : null);
@endphp

@if ($data && isset($data['message']))
@php
    $toastType = $data['type'] ?? 'success';
    $toastMessage = $data['message'];
    $toastTimer = (int) ($data['timer'] ?? 3000);
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.toast && window.toast[{{ Js::from($toastType) }}]) {
        window.toast[{{ Js::from($toastType) }}]({{ Js::from($toastMessage) }}, { timer: {{ $toastTimer }} });
    }
});
</script>
@endif
