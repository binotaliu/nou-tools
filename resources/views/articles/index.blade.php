@push('head')
    <x-json-ld
        :data="[
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $viewModel->type->label() . ' - NOU 小幫手',
            'url' => url()->current(),
        ]"
    />
@endpush

<x-layout :title="$viewModel->type->label() . ' - NOU 小幫手'">
    <div class="mx-auto max-w-4xl">
        <x-card>
            <div class="prose max-w-none prose-warm dark:prose-invert">
                {{ $viewModel->indexContent }}
            </div>
        </x-card>
    </div>
</x-layout>
