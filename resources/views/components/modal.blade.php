@props([
    'name',
    'title' => '',
    'description' => '',
    'maxWidth' => 'max-w-md',
])

<div
    x-cloak
    x-show="{{ $name }}"
    x-transition.opacity.scale.95.duration.150
    @keydown.escape.window="{{ $name }} = false"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:items-center sm:p-0"
>
    <template x-teleport="body">
        <div
            class="fixed inset-0 bg-black/40"
            @click="{{ $name }} = false"
            x-show="{{ $name }}"
            aria-hidden="true"
        ></div>
    </template>

    <div
        role="dialog"
        aria-modal="true"
        @click.outside="{{ $name }} = false"
        {{ $attributes->merge(['class' => "relative w-full {$maxWidth} max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-lg bg-white p-6 shadow-lg sm:max-h-[calc(100vh-2rem)]"]) }}
    >
        @if ($title)
            <h3 class="mb-2 text-lg font-semibold text-warm-900">
                {{ $title }}
            </h3>
        @endif

        @if ($description)
            <p class="mb-4 text-sm text-warm-600">{{ $description }}</p>
        @endif

        {{ $slot }}

        @isset($footer)
            <div class="mt-4 flex justify-end">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
