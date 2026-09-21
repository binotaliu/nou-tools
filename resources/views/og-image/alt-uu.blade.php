{{-- Social card for the Alt UU page: the same light plus-pattern layout as the
static public/og-image.png (icon left, title and tagline right), with the
academic-cap mark the page hero uses. Rendered into the Inertia root view
(app.blade.php) and screenshotted by spatie/laravel-og-image. The pattern is an
inline <svg> because the shared plus.svg tile is white at 10% opacity, which
would vanish on this light background. --}}
<x-og-image>
    <div
        class="relative flex h-full w-full items-center justify-center gap-16 overflow-hidden bg-white pb-4"
    >
        <svg class="absolute inset-0 h-full w-full" aria-hidden="true">
            <defs>
                <pattern
                    id="plus"
                    x="26"
                    y="18"
                    width="158"
                    height="158"
                    patternUnits="userSpaceOnUse"
                >
                    <path
                        fill="#f3f3f3"
                        fill-rule="evenodd"
                        transform="scale(2.63)"
                        d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2zM36 4V0h-2v4h-4v2h4v4h2V6h4V4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2zM6 4V0H4v4H0v2h4v4h2V6h4V4z"
                    />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#plus)" />
        </svg>

        <x-heroicon-o-academic-cap
            class="relative size-52 shrink-0 text-theme-600"
        />
        <div class="relative">
            <h1 class="text-8xl leading-none font-bold text-theme-600">
                Alt UU
            </h1>
            <p class="mt-6 text-4xl text-theme-600">給 NOU 同學的 UU 平台瀏覽器 App</p>
        </div>
    </div>
</x-og-image>
