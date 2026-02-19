@props(['type' => 'info'])

@php
  $styles = match ($type) {
    'success' => 'border-l-4 border-green-500 bg-green-100 text-green-700',
    'error' => 'border-l-4 border-red-500 bg-red-100 text-red-700',
    'warning' => 'border-l-4 border-yellow-400 bg-yellow-50 text-yellow-800',
    default => 'border-l-4 border-blue-500 bg-blue-100 text-blue-700',
  };
@endphp

<div {{ $attributes->merge(['class' => "mb-6 p-4 rounded {$styles}"]) }}>
  {{ $slot }}
</div>
