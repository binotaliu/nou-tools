<button
    type="{{ $type }}"
    {{ $disabled ? 'disabled' : '' }}
    class="{{ $getClasses() }}"
    {{ $attributes }}
>
    {{ $slot }}
</button>
