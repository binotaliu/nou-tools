@props(['type' => 'info'])

@php
    $styles = match ($type) {
        'success' => 'bg-green-100 border-l-4 border-green-500 text-green-700',
        'error'   => 'bg-red-100 border-l-4 border-red-500 text-red-700',
        'warning' => 'bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800',
        default   => 'bg-blue-100 border-l-4 border-blue-500 text-blue-700',
    };
@endphp

<div {{ $attributes->merge(['class' => "mb-6 p-4 rounded {$styles}"]) }}>
    {{ $slot }}
</div>
