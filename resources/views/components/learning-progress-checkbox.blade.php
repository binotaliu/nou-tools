<div class="grid size-4 grid-cols-1">
    <input
        type="checkbox"
        value="1"
        {{ $attributes->class('col-start-1 row-start-1 size-4 appearance-none rounded border border-gray-500 bg-white checked:border-gray-400 print:hidden') }}
    />
    <x-heroicon-s-check
        class="col-start-1 row-start-1 m-0.5 size-3 text-gray-400 opacity-0 group-has-checked:opacity-100 print:hidden"
    />

    {{-- empty box for print --}}
    <div
        class="col-start-1 row-start-1 hidden size-4 rounded border border-gray-500 bg-white print:block"
    ></div>
</div>
