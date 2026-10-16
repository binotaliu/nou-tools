<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div
        class="overflow-x-auto rounded-lg border border-gray-200 font-mono text-sm leading-6 dark:border-white/10"
    >
        @forelse ($getState() ?? [] as $line)
            @if ($line->type === 'gap')
                <div
                    class="bg-gray-50 px-4 py-1 text-xs text-gray-500 dark:bg-white/5 dark:text-gray-400"
                >
                    ⋯ 略過 {{ $line->text }} 行未變更的內容
                </div>
            @else
                <div
                    @class([
                    'flex gap-3 whitespace-pre-wrap px-4',
                    'bg-success-50 text-success-800 dark:bg-success-950 dark:text-success-200' => $line->type === 'added',
                    'bg-danger-50 text-danger-800 dark:bg-danger-950 dark:text-danger-200' => $line->type === 'removed',
                ])
                >
                    <span
                        class="w-3 shrink-0 select-none"
                        >{{ ['added' => '+', 'removed' => '−'][$line->type] ?? ' ' }}</span
                    ><span
                        class="min-w-0 break-words"
                        >{{ $line->text === '' ? ' ' : $line->text }}</span
                    >
                </div>
            @endif
        @empty
            <p class="px-4 py-3 text-gray-500">內容沒有變更。</p>
        @endforelse
    </div>
</x-dynamic-component>
