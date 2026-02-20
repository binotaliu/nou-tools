<a
    href="{{ $href }}"
    @if ($target)
        target="{{ $target }}"
    @endif
    @if ($rel)
        rel="{{ $rel }}"
    @endif
    @if ($download)
        download
    @endif
    class="{{ $getClasses() }}"
    {{ $attributes }}
>
    {{ $slot }}
</a>
