@props(['wrapperClass' => ''])
<div @class(['relative', $wrapperClass])>
    <select
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 appearance-none']) }}
    >
        {{ $slot }}
    </select>

    <div
        class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
    >
        <x-heroicon-o-chevron-down class="size-5 text-gray-400" />
    </div>
</div>
