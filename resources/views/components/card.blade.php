@props(['class' => ''])

<div
    {{ $attributes->merge(['class' => 'bg-white p-6 rounded-lg border border-warm-200 ' . $class]) }}
>
    {{ $slot }}
</div>
