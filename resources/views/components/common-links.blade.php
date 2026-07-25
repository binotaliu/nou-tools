@props([
    'customLinks' => [],
])

<x-card {{ $attributes->merge(['title' => '常用連結'])->class('w-full md:w-auto') }}>
    <div
        class="grid grid-cols-1 gap-2 md:grid-cols-3 md:flex-row md:items-center"
    >
        <x-common-link href="https://www.nou.edu.tw">
            <x-slot:icon>
                <x-heroicon-o-academic-cap class="size-16" />
            </x-slot>
            學校官網
        </x-common-link>

        <x-common-link href="https://noustud.nou.edu.tw/">
            <x-slot:icon>
                <x-heroicon-o-computer-desktop class="size-16" />
            </x-slot>
            教務行政資訊系統
        </x-common-link>

        <x-common-link href="https://uu.nou.edu.tw/">
            <x-slot:icon>
                <x-heroicon-o-globe-alt class="size-16" />
            </x-slot>
            數位學習平台 (UU平台)
        </x-common-link>
    </div>

    @if (count($customLinks) > 0)
        <div
            class="mt-4 flex flex-wrap items-start gap-x-4 gap-y-2 rounded border-2 border-dotted border-warm-700/50 px-4 py-2"
        >
            @foreach ($customLinks as $link)
                <x-link-button
                    :href="$link->url"
                    variant="secondary"
                    class="mb-0"
                >
                    <x-heroicon-o-link class="size-4" />
                    {{ $link->title }}
                </x-link-button>
            @endforeach
        </div>
    @endif
</x-card>
