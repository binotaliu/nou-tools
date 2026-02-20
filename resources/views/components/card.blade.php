@props(['class' => '', 'title' => null, 'subtitle' => null, 'titleTag' => 'h3'])

<div
    {{ $attributes->merge(['class' => 'bg-white p-6 rounded-lg border border-warm-200 ' . $class]) }}
>
    @if ($title || $subtitle)
        <div class="mb-4">
            @if ($title)
                <{{ $titleTag }}
                    class="mb-1 text-xl font-semibold text-warm-900"
                >
                    {{ $title }}
                </{{ $titleTag }}>
            @endif

            @if ($subtitle)
                <div class="text-sm text-warm-600">
                    {{ $subtitle }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
