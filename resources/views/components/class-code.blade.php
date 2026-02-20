@props(['code' => null])

<span
    {{ $attributes->merge(['class' => 'inline-block rounded bg-warm-100 print:bg-transparent px-2 print:p-0 py-1 font-mono font-normal text-xs text-warm-800']) }}
>
    {{ $code ?? $slot }}
</span>
