{{-- Shared social card for the discount store pages, rendered into the Inertia
root view (app.blade.php) and screenshotted by spatie/laravel-og-image. The
background is the Hero Patterns "I Like Food" tile (public/images/i-like-food.svg,
recoloured white at 10% opacity so it reads as texture, not content).

Params: eyebrow, title, kicker (optional, above the title), subtitle (optional). --}}
<x-og-image>
    <div
        class="relative flex h-full w-full flex-col justify-between overflow-hidden bg-theme-700 bg-[url('/images/i-like-food.svg')] bg-[length:390px_390px] p-16 text-white"
    >
        {{-- Darkens towards the bottom so the title stays legible over the pattern. --}}
        <div
            class="absolute inset-0 bg-gradient-to-t from-theme-900/90 via-theme-900/50 to-transparent"
        ></div>

        <div class="relative flex items-start justify-between gap-8">
            <p class="text-3xl font-medium tracking-wide text-theme-100">
                {{ $eyebrow }}
            </p>
            {{-- Same mark as the site navbar (AppLayout.vue): book-open icon + name. --}}
            <div
                class="flex shrink-0 items-center gap-3 rounded-full bg-white px-6 py-3 text-3xl font-bold text-theme-700 shadow-lg"
            >
                <x-heroicon-o-book-open class="size-8 shrink-0" />
                <span>NOU 小幫手</span>
            </div>
        </div>
        <div class="relative space-y-4">
            @if (! empty($kicker))
                <p
                    class="inline-block rounded-full bg-white/15 px-6 py-2 text-3xl font-medium text-white"
                >
                    {{ $kicker }}
                </p>
            @endif
            <h1 class="line-clamp-2 text-7xl leading-tight font-bold">
                {{ $title }}
            </h1>
            @if (! empty($subtitle))
                <p class="line-clamp-2 text-3xl text-theme-100">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
    </div>
</x-og-image>
