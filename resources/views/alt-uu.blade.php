<x-layout
    title="Alt UU - NOU 小幫手"
    description="Alt UU 是一款由學生開發的手機 App，讓你在行動裝置上方便地存取 UU 平台教材，隨時隨地學習。支援 iPhone、iPad、macOS 及 Android。"
>
    <div class="space-y-10">
        {{-- Hero --}}
        <div
            class="rounded-xl border border-warm-200 bg-white px-8 py-12 text-center"
        >
            <p
                class="text-sm font-medium tracking-widest text-warm-500 uppercase"
            >
                手機 App
            </p>
            <h2 class="mt-2 text-4xl font-bold tracking-tight text-warm-900">
                Alt UU
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-warm-600">
                由學生開發、專為 NOU 同學打造的 UU 平台瀏覽器 App。
                <br class="hidden sm:inline" />
                隨時隨地在行動裝置上輕鬆學習。
            </p>
            <p class="mt-2 text-sm text-warm-400">
                備註：你必須擁有有效的 UU 平台帳號，才可以使用本 App。
            </p>

            <div
                class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row"
            >
                <x-link-button
                    href="https://apps.apple.com/tw/app/alt-uu/id6760690577"
                    variant="warm-dark"
                    target="_blank"
                    rel="noopener noreferrer"
                    size="lg"
                >
                    <x-heroicon-o-device-phone-mobile class="size-5" />
                    App Store 下載
                </x-link-button>

                <x-link-button
                    href="https://docs.google.com/forms/d/e/1FAIpQLSe4TJ3vDrj2ohQBdGbzimj62W2rA-rQaKVJqymdvAkD_VVsSA/viewform?usp=header"
                    variant="secondary"
                    target="_blank"
                    rel="noopener noreferrer"
                    size="lg"
                >
                    <x-heroicon-o-device-phone-mobile class="size-5" />
                    Android 封測申請
                </x-link-button>
            </div>

            <p class="mt-3 text-xs text-warm-400">
                App Store 支援 iPhone、iPad 及 Mac（Apple
                Silicon）&nbsp;·&nbsp;Android 版目前開放封測申請
            </p>
        </div>

        {{-- Features --}}
        <div>
            <h3 class="mb-4 text-xl font-semibold text-warm-800">
                App 功能介紹
            </h3>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <x-card>
                    <div
                        class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-800 ring-1 ring-amber-300/70"
                    >
                        <x-heroicon-o-academic-cap class="size-6" />
                    </div>
                    <h4 class="mb-2 text-lg font-semibold text-warm-900">
                        瀏覽 UU 平台教材
                    </h4>
                    <p class="text-sm text-warm-600">
                        以原生 App 體驗瀏覽 UU 平台，讓你在 iPhone、Mac 或
                        Android 裝置上方便存取所有課程教材，不受瀏覽器限制。
                    </p>
                </x-card>

                <x-card>
                    <div
                        class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-500/15 text-sky-800 ring-1 ring-sky-300/70"
                    >
                        <x-heroicon-o-clock class="size-6" />
                    </div>
                    <h4 class="mb-2 text-lg font-semibold text-warm-900">
                        保存學習時數
                    </h4>
                    <p class="text-sm text-warm-600">
                        開啟教材後，畫面右上方會顯示本次學習計時器。觀看完畢後點擊返回按鈕，即可自動保存本次學習時數。
                    </p>
                </x-card>

                <x-card>
                    <div
                        class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500/15 text-emerald-800 ring-1 ring-emerald-300/70"
                    >
                        <x-heroicon-o-puzzle-piece class="size-6" />
                    </div>
                    <h4 class="mb-2 text-lg font-semibold text-warm-900">
                        NOU 小幫手整合
                    </h4>
                    <p class="text-sm text-warm-600">
                        支援整合「NOU 小幫手」，開啟後即可在 App
                        內直接查看學校行事曆、視訊面授資訊，以及考古題等。
                    </p>
                </x-card>
            </div>
        </div>

        {{-- Download CTA --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-card class="flex flex-col items-start gap-4">
                <div>
                    <p
                        class="text-xs font-semibold tracking-widest text-warm-400 uppercase"
                    >
                        iPhone / iPad / Mac
                    </p>
                    <h4 class="mt-1 text-xl font-semibold text-warm-900">
                        App Store
                    </h4>
                    <p class="mt-2 text-sm text-warm-600">
                        支援 iPhone、iPad 及搭載 Apple Silicon 的 Mac
                        電腦。直接從 App Store 下載安裝，無需額外設定。
                    </p>
                </div>
                <x-link-button
                    href="https://apps.apple.com/tw/app/alt-uu/id6760690577"
                    variant="warm-dark"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-auto"
                >
                    前往 App Store
                </x-link-button>
            </x-card>

            <x-card class="flex flex-col items-start gap-4">
                <div>
                    <p
                        class="text-xs font-semibold tracking-widest text-warm-400 uppercase"
                    >
                        Android
                    </p>
                    <h4 class="mt-1 text-xl font-semibold text-warm-900">
                        Google Play 封測
                    </h4>
                    <p class="mt-2 text-sm text-warm-600">
                        Android
                        版目前正在封測階段。填寫申請表單後即可加入封測，搶先體驗
                        Android 版功能。
                    </p>
                </div>
                <x-link-button
                    href="https://docs.google.com/forms/d/e/1FAIpQLSe4TJ3vDrj2ohQBdGbzimj62W2rA-rQaKVJqymdvAkD_VVsSA/viewform?usp=header"
                    variant="secondary"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-auto"
                >
                    申請加入封測
                </x-link-button>
            </x-card>
        </div>
    </div>
</x-layout>
