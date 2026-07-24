@props(['class' => '', 'title' => null, 'subtitle' => null, 'titleTag' => 'h2'])

<div
    {{ $attributes->merge(['class' => 'bg-white dark:bg-zinc-900 p-6 rounded-lg border border-warm-200 dark:border-zinc-700 ' . $class]) }}
>
    @if ($title || $subtitle)
        <div class="mb-4">
            @if ($title)
                <{{ $titleTag }}
                    class="mb-1 text-xl font-semibold text-warm-900 dark:text-zinc-100"
                >
                    {{ $title }}
                </{{ $titleTag }}>
            @endif

            @if ($subtitle)
                <div class="text-sm text-warm-600 dark:text-zinc-400">
                    {{ $subtitle }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
