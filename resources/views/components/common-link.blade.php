@props([
    'href',
])

<a
    href="{{ $href }}"
    target="_blank"
    rel="noopener noreferrer"
    {{ $attributes->merge(['class' => 'inline-flex flex-col justify-center items-center px-3 py-2 text-base bg-white dark:bg-zinc-900 border border-warm-200 dark:border-zinc-700 text-warm-700 dark:text-zinc-300 rounded hover:bg-warm-50 dark:hover:bg-zinc-950 gap-2 truncate']) }}
>
    @isset($icon)
        {{ $icon }}
    @endisset

    {{ $slot }}
</a>
