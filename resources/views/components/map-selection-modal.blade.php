@props([
    'title' => '選擇地圖 App',
    'description' => '選擇你慣用的地圖應用程式來檢視位置。',
    'osmAction' => "openInMap('osm')",
    'appleAction' => "openInMap('apple')",
    'googleAction' => "openInMap('google')",
])

<template x-if="showMapSelectionModal">
    <template x-teleport="body">
        <div
            x-transition.opacity
            class="fixed inset-0 z-1100 flex items-center justify-center bg-black/50"
            @click.self="closeMapSelectionModal()"
        >
            <div
                class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900"
            >
                <h3
                    class="mb-4 text-lg font-semibold text-warm-900 dark:text-zinc-100"
                >
                    {{ $title }}
                </h3>
                <p class="mb-6 text-sm text-warm-600 dark:text-zinc-400">
                    {{ $description }}
                </p>
                <div class="space-y-2">
                    <button
                        type="button"
                        @click="{{ $osmAction }}"
                        class="w-full rounded-lg border border-warm-200 px-4 py-3 text-center text-sm font-medium text-warm-700 transition hover:bg-warm-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
                    >
                        在 OpenStreetMap 開啟
                    </button>
                    <button
                        type="button"
                        @click="{{ $appleAction }}"
                        class="w-full rounded-lg border border-warm-200 px-4 py-3 text-center text-sm font-medium text-warm-700 transition hover:bg-warm-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
                    >
                        在 Apple 地圖開啟
                    </button>
                    <button
                        type="button"
                        @click="{{ $googleAction }}"
                        class="w-full rounded-lg border border-warm-200 px-4 py-3 text-center text-sm font-medium text-warm-700 transition hover:bg-warm-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
                    >
                        在 Google 地圖開啟
                    </button>
                </div>
                <button
                    type="button"
                    class="mt-4 w-full rounded-lg border border-warm-200 px-4 py-2 text-sm text-warm-700 transition hover:bg-warm-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
                    @click="closeMapSelectionModal()"
                >
                    關閉
                </button>
            </div>
        </div>
    </template>
</template>
