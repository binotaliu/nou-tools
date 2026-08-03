<a
    href="{{ $href }}"
    @if ($target)
        target="{{ $target }}"
    @endif
    @if ($rel)
        rel="{{ $rel }}"
    @endif
    @if (is_string($download))
        download="{{ $download }}"
    @elseif ($download)
        download
    @endif
    {{ $attributes->class($getClasses()) }}
>
    {{ $slot }}
</a>
