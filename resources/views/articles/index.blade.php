<x-layout :title="$type->label() . ' - NOU 小幫手'">
    <div class="mx-auto max-w-4xl">
        <x-card>
            <div class="prose max-w-none prose-warm">
                {{ $indexContent }}
            </div>
        </x-card>
    </div>
</x-layout>
