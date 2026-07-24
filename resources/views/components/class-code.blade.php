@props(['code' => null])

<span
    {{ $attributes->merge(['class' => 'inline-block rounded bg-warm-100 dark:bg-zinc-800 print:bg-transparent px-2 print:p-0 py-1 font-mono font-normal text-xs text-warm-800 dark:text-zinc-200']) }}
>
    <span class="sr-only">班級代碼：</span>
    {{ $code ?? $slot }}
</span>
