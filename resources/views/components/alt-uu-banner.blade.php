<div
    {{ $attributes->merge(['class' => 'mb-6']) }}
    x-data="{
        storageKey: 'alt_uu_promo_banner_dismissed_v1',
        visible: true,
        init() {
            this.visible = localStorage.getItem(this.storageKey) !== '1'
        },
        dismiss() {
            this.visible = false
            localStorage.setItem(this.storageKey, '1')
        },
    }"
    x-show="visible"
    x-transition.opacity.duration.200ms
    x-cloak
>
    <div class="relative rounded-lg border border-amber-300" role="region">
        <button
            type="button"
            @click="dismiss()"
            class="absolute top-4 right-4 inline-flex items-center justify-center rounded-md border border-warm-600 bg-white p-1.5 text-warm-700 transition hover:bg-warm-100 hover:text-warm-900 focus:ring-2 focus:ring-warm-500 focus:outline-none dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-900 dark:hover:text-zinc-100"
            aria-label="關閉 Alt UU 宣傳"
        >
            <x-heroicon-o-x-mark class="size-4" />
        </button>

        <div
            class="flex flex-col overflow-hidden rounded-lg bg-white sm:flex-row dark:bg-zinc-900"
        >
            <div
                class="flex h-24 min-h-24 items-center justify-center bg-amber-500/10 px-4 text-amber-800 sm:h-auto sm:w-24 sm:px-3 dark:bg-amber-500/15 dark:text-amber-400"
            >
                <x-heroicon-o-gift class="size-6" />
            </div>

            <div
                class="flex flex-1 flex-col justify-between gap-4 px-4 py-4 text-amber-900 sm:pr-12 md:px-5 md:py-5 md:pr-12 dark:text-amber-200"
            >
                <p class="text-sm leading-6 md:text-base">全新 App「Alt UU」現已在 iOS 上全面推出。使用 Alt UU 可讓你在行動裝置上輕鬆存取 NOU 數位教材。另有 Android 版本封測開放申請中。</p>

                <div
                    class="flex flex-wrap items-center justify-end gap-2 sm:-mr-8"
                >
                    <x-link-button :href="route('alt-uu')" variant="primary">
                        瞭解更多
                    </x-link-button>
                </div>
            </div>
        </div>
    </div>
</div>
