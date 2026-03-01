@props(['caption' => null])

<table
    {{ $attributes->merge(['class' => 'w-full border-collapse overflow-hidden rounded text-left text-sm text-warm-700']) }}
>
    @if ($caption)
        <caption class="sr-only">{{ $caption }}</caption>
    @endif

    {{ $slot }}
</table>
