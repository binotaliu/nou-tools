<x-layout
    title="Alt UU - NOU 小幫手"
    description="Alt UU 是一款由學生開發的手機 App，讓你在行動裝置上方便地存取 UU 平台教材，隨時隨地學習。支援 iPhone、iPad、macOS 及 Android。"
>
    <div class="space-y-10">
        <div
            class="rounded-xl border border-warm-200 bg-white px-8 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900"
        >
            <div class="flex w-full justify-center" aria-hidden>
                <x-heroicon-o-academic-cap
                    class="size-16 text-warm-600 dark:text-zinc-400"
                />
            </div>
            <h2
                class="mt-2 text-4xl font-bold tracking-tight text-warm-600 dark:text-zinc-400"
            >
                Alt UU
            </h2>
            <p
                class="mx-auto mt-4 max-w-xl text-lg text-warm-600 dark:text-zinc-400"
            >
                專為 NOU 同學打造的 UU 平台瀏覽器 App。
                <br class="hidden sm:inline" />
                隨時隨地在行動裝置上輕鬆學習。
            </p>

            <div
                class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row"
            >
                <a
                    href="https://apps.apple.com/tw/app/alt-uu/id6760690577"
                    variant="warm-dark"
                    target="_blank"
                    rel="noopener noreferrer"
                    size="lg"
                >
                    <x-icon-app-store-download
                        class="h-14"
                        aria-label="在 App Store 上下載"
                    />
                </a>

                <a
                    class="inline-flex h-14 w-38 items-center justify-start gap-2 rounded-lg border border-warm-500 bg-white px-2 py-1 font-semibold text-warm-900 transition hover:bg-warm-50 disabled:border-warm-200 disabled:bg-warm-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-950 dark:disabled:border-zinc-700 dark:disabled:bg-zinc-950"
                    href="https://docs.google.com/forms/d/e/1FAIpQLSe4TJ3vDrj2ohQBdGbzimj62W2rA-rQaKVJqymdvAkD_VVsSA/viewform?usp=header"
                    target="_blank"
                    rel="noopener noreferrer"
                    size="lg"
                >
                    <x-heroicon-o-device-phone-mobile class="size-8" />
                    <span
                        class="flex flex-col justify-center text-left leading-0"
                    >
                        <span class="text-xl leading-4">Android</span>
                        <span class="text-sm leading-4">封測申請</span>
                    </span>
                </a>
            </div>

            <p class="mt-3 text-xs text-warm-400 dark:text-zinc-500">
                App Store 支援 iPhone、iPad 及 Mac（Apple
                Silicon）&nbsp;·&nbsp;Android 版封閉測試中
            </p>
        </div>

        {{-- Features --}}
        <div>
            <h3
                class="sr-only mb-4 text-xl font-semibold text-warm-800 dark:text-zinc-200"
            >
                App 功能介紹
            </h3>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-card>
                    <div
                        class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-800 ring-1 ring-amber-300/70 dark:bg-amber-500/20 dark:text-amber-400 dark:ring-amber-400/40"
                    >
                        <x-heroicon-o-academic-cap class="size-6" />
                    </div>
                    <h4
                        class="mb-2 text-lg font-semibold text-warm-900 dark:text-zinc-100"
                    >
                        瀏覽 UU 平台教材
                    </h4>
                    <p class="text-sm text-warm-600 dark:text-zinc-400">
                        以 App 瀏覽 UU
                        平台，讓你在行動裝置上輕鬆存取所有課程教材，不需使用瀏覽器開啟電腦版網頁。
                    </p>
                </x-card>

                <x-card>
                    <div
                        class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-500/15 text-sky-800 ring-1 ring-sky-300/70 dark:bg-sky-500/20 dark:text-sky-400 dark:ring-sky-400/40"
                    >
                        <x-heroicon-o-clock class="size-6" />
                    </div>
                    <h4
                        class="mb-2 text-lg font-semibold text-warm-900 dark:text-zinc-100"
                    >
                        保存學習時數
                    </h4>
                    <p class="text-sm text-warm-600 dark:text-zinc-400">
                        開啟教材後，畫面右上方會顯示本次學習計時器。觀看完畢後點擊返回按鈕，即可自動保存本次學習時數。
                    </p>
                </x-card>

                <x-card>
                    <div
                        class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-500/15 text-violet-800 ring-1 ring-violet-300/70 dark:bg-violet-500/20 dark:text-violet-400 dark:ring-violet-400/40"
                    >
                        <x-heroicon-o-play class="size-6" />
                    </div>
                    <h4
                        class="mb-2 text-lg font-semibold text-warm-900 dark:text-zinc-100"
                    >
                        繼續上次觀看進度
                    </h4>
                    <p class="text-sm text-warm-600 dark:text-zinc-400">
                        Alt UU
                        會記住你上次觀看的位置，讓你再次開啟教材時能直接從上次離開的地方繼續學習。
                    </p>
                </x-card>

                <x-card>
                    <div
                        class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500/15 text-emerald-800 ring-1 ring-emerald-300/70 dark:bg-emerald-500/20 dark:text-emerald-400 dark:ring-emerald-400/40"
                    >
                        <x-heroicon-o-puzzle-piece class="size-6" />
                    </div>
                    <h4
                        class="mb-2 text-lg font-semibold text-warm-900 dark:text-zinc-100"
                    >
                        NOU 小幫手整合
                    </h4>
                    <p class="text-sm text-warm-600 dark:text-zinc-400">
                        支援整合「NOU 小幫手」，開啟後即可在 App
                        內直接檢視學校行事曆、視訊面授資訊，以及考古題等。
                    </p>
                </x-card>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-card class="flex flex-col items-start gap-4">
                <div>
                    <p
                        class="text-xs font-semibold text-warm-400 dark:text-zinc-500"
                    >
                        iPhone / iPad / Mac
                    </p>
                    <h4
                        class="mt-1 text-xl font-semibold text-warm-900 dark:text-zinc-100"
                    >
                        App Store
                    </h4>
                    <p class="mt-2 text-sm text-warm-600 dark:text-zinc-400">
                        支援 iPhone、iPad，以及搭載 Apple Silicon 的 Mac。
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
                        class="text-xs font-semibold text-warm-400 dark:text-zinc-500"
                    >
                        Android
                    </p>
                    <h4
                        class="mt-1 text-xl font-semibold text-warm-900 dark:text-zinc-100"
                    >
                        Google Play 封測
                    </h4>
                    <p class="mt-2 text-sm text-warm-600 dark:text-zinc-400">
                        Android 版目前正在封測階段。
                        <br />
                        填寫申請表單後即可加入封測，搶先體驗 Android 版功能。
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

        <!-- Trademark Credit -->
        <div
            class="flex flex-col gap-2 text-xs text-warm-400 dark:text-zinc-500"
        >
            <p>
                使用 Alt UU，你必須同意並遵守 Alt UU 的《
                <a
                    href="https://alt-uu-statics.wcsvdzeimhwq.workers.dev/usage-policy"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-warm-600 underline hover:text-warm-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                >
                    使用條款
                </a>
                》與 《
                <a
                    href="https://alt-uu-statics.wcsvdzeimhwq.workers.dev/privacy-policy"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-warm-600 underline hover:text-warm-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                >
                    隱私權政策
                </a>
                》。
            </p>
            <p>
                Alt UU 並非 NOU 官方產品，僅為學生開發的第三方 App，與 NOU
                官方無任何隸屬或合作關係。有關 Alt UU 之運作方式，請參閱《
                <a
                    href="https://alt-uu-statics.wcsvdzeimhwq.workers.dev/app-architecture"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-warm-600 underline hover:text-warm-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                >
                    Alt UU 運作原理說明
                </a>
                》。
            </p>
            <p>
                Alt UU 是自由且開放之軟體，原始碼以 AGPL-3.0-or-later
                授權條款公開於
                <a
                    href="https://github.com/binotaliu/alt-uu"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-warm-600 underline hover:text-warm-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                >
                    GitHub
                </a>
                ，歡迎任何人檢視、審閱、使用、修改或貢獻程式碼。
            </p>
            <p>
                iPhone, iPad, Mac, and App Store are trademarks of Apple Inc.,
                registered in the U.S. and other countries.
                <br />
                Android is a trademark of Google LLC.
                <br />
                This site is not endorsed by or affiliated with Apple Inc. or
                Google LLC.
            </p>
        </div>
    </div>
</x-layout>
