{{-- Rendered by RenderWikiMarkdown, which strips raw HTML from the draft. --}}
<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div
        class="[&_h2]:mt-4 [&_h2]:text-lg [&_h2]:font-bold [&_h3]:mt-3 [&_h3]:font-semibold [&_li]:ml-5 [&_ol]:list-decimal [&_p]:my-2 [&_ul]:list-disc [&_a]:underline max-h-[40rem] overflow-y-auto rounded-lg border border-gray-200 p-4 text-sm leading-7 dark:border-white/10"
    >
        {!! $getState() !!}
    </div>
</x-dynamic-component>
