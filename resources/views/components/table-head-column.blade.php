@php
    // default classes for table header column
    $attrs = $attributes->class('px-4 py-3 font-bold text-warm-900');

    if (! $attrs->has('scope')) {
        $attrs = $attrs->merge(['scope' => 'col']);
    }
@endphp

<th {{ $attrs }}>
    {{ $slot }}
</th>
