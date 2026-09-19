{{-- Social card for a newsletter issue (drafts too, so admins can preview it
with ?ogimage); rendered into the Inertia
root view (app.blade.php) and screenshotted by spatie/laravel-og-image. --}}
@php
    $issue = $props['viewModel']['issue'] ?? null;
@endphp
@if ($issue)
    <x-og-image>
        <div
            class="relative flex h-full w-full flex-col justify-between overflow-hidden bg-theme-800 p-16 text-white"
        >
            @if ($issue['coverImageUrl'])
                <img
                    src="{{ $issue['coverImageUrl'] }}"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                />
            @endif
            <div
                class="absolute inset-0 bg-gradient-to-t from-theme-900/95 via-theme-900/60 to-theme-900/20"
            ></div>

            <div class="relative flex items-start justify-between gap-8">
                <p class="text-3xl font-medium tracking-wide text-theme-100">
                    {{ $props['viewModel']['newsletterTitle'] }}・{{ $issue['issueKey'] }}
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
                <h1 class="text-6xl leading-tight font-bold">
                    {{ $issue['title'] }}
                </h1>
                <p class="text-3xl text-theme-200">
                    {{ \Illuminate\Support\Carbon::parse($issue['publishesOn'])->isoFormat('YYYY 年 M 月 D 日') }} 發刊
                </p>
            </div>
        </div>
    </x-og-image>
@endif
