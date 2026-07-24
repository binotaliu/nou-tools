@props(['type' => 'info'])

@php
    $styles = match ($type) {
        'success' => 'border-l-4 border-green-500 bg-green-100 text-green-700 dark:border-green-600 dark:bg-green-950/60 dark:text-green-300',
        'error' => 'border-l-4 border-red-500 bg-red-100 text-red-700 dark:border-red-600 dark:bg-red-950/60 dark:text-red-300',
        'warning' => 'border-l-4 border-yellow-400 bg-yellow-50 text-yellow-800 dark:border-yellow-600 dark:bg-yellow-950/60 dark:text-yellow-300',
        default => 'border-l-4 border-blue-500 bg-blue-100 text-blue-700 dark:border-blue-600 dark:bg-blue-950/60 dark:text-blue-300',
    };
@endphp

<div {{ $attributes->merge(['class' => "mb-6 p-4 rounded {$styles}"]) }}>
    {{ $slot }}
</div>
