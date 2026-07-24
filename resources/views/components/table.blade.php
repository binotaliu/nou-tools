@props(['caption' => null])

<table
    {{ $attributes->merge(['class' => 'w-full border-collapse overflow-hidden rounded text-left text-warm-700 dark:text-zinc-300']) }}
>
    @if ($caption)
        <caption class="sr-only">{{ $caption }}</caption>
    @endif

    {{ $slot }}
</table>
