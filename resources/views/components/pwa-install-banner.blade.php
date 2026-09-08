<div
    {{ $attributes->merge(['class' => 'mb-6 print:hidden']) }}
    x-data="nouPwaInstallBanner"
    x-show="visible"
    x-transition.opacity.duration.200ms
    x-cloak
>
    <div
        class="relative rounded-lg border border-warm-300 dark:border-zinc-600"
        role="region"
    >
        <button
            type="button"
            @click="dismiss()"
            class="absolute top-4 right-4 inline-flex items-center justify-center rounded-md border border-warm-600 bg-white p-1.5 text-warm-700 transition hover:bg-warm-100 hover:text-warm-900 focus:ring-2 focus:ring-warm-500 focus:outline-none dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-900 dark:hover:text-zinc-100"
            aria-label="關閉安裝提示"
        >
            <x-heroicon-o-x-mark class="size-4" />
        </button>

        <div
            class="flex flex-col overflow-hidden rounded-lg bg-white sm:flex-row dark:bg-zinc-900"
        >
            <div
                class="flex h-24 min-h-24 items-center justify-center bg-warm-500/10 px-4 text-warm-700 sm:h-auto sm:w-24 sm:px-3 dark:bg-warm-500/15 dark:text-warm-400"
            >
                <x-heroicon-o-device-phone-mobile class="size-6" />
            </div>

            <div
                class="flex flex-1 flex-col justify-between gap-4 px-4 py-4 text-warm-900 sm:pr-12 md:px-5 md:py-5 md:pr-12 dark:text-warm-100"
            >
                <p class="text-sm leading-6 md:text-base" x-cloak x-show="
                        !isIos
                    ">將「NOU 小幫手」安裝到裝置上，即可像一般 App 一樣從主畫面開啟，並支援離線檢視此課表。</p>
                <p class="text-sm leading-6 md:text-base" x-cloak x-show="
                        isIos
                    ">將「NOU 小幫手」加入主畫面，即可像一般 App 一樣開啟：點選瀏覽器下方的
                <x-heroicon-o-arrow-up-on-square class="inline size-4" />
                分享圖示，再選擇「加入主畫面」。</p>

                <div
                    class="flex flex-wrap items-center justify-end gap-2 sm:-mr-8"
                    x-show="!isIos"
                    x-cloak
                >
                    <x-button
                        type="button"
                        variant="primary"
                        @click="install()"
                    >
                        安裝為 App
                    </x-button>
                </div>
            </div>
        </div>
    </div>
</div>
