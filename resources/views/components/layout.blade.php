@props([
    'title' => 'NOU 小幫手',
    'description' => '給 NOU 同學的非官方小工具：管理個人課表與學習進度',
    'noindex' => false,
])

@php
    $routeName = request()
        ->route()
        ?->getName();

    $markdownRouteName = match ($routeName) {
        'home' => 'llms-txt',
        default => $routeName ? $routeName.'.md' : null,
    };

    $markdownUrl = $markdownRouteName && \Illuminate\Support\Facades\Route::has($markdownRouteName)
        ? route($markdownRouteName, request()->route()->parameters())
        : null;

    $scheduleNavHref = route('schedules.my');

    $analyticsPage = match ($routeName) {
        'schedules.show' => '/schedules/:schedule',
        'schedules.edit' => '/schedules/:schedule/edit',
        'learning-progress.show' => '/schedules/:schedule/learning-progress',
        default => '/' . ltrim(request()->path(), '/'),
    };

    $analyticsTitle = match ($routeName) {
        'schedules.show' => '我的課表 - NOU 小幫手',
        'schedules.create' => '新增課表 - NOU 小幫手',
        'schedules.edit' => '編輯課表 - NOU 小幫手',
        'learning-progress.show' => '學習進度表 - NOU 小幫手',
        default => $title,
    };
@endphp

<!DOCTYPE html>
<html lang="zh-hant">
<head>
    <!--
        For AI agents: this page has a Markdown version that is easier to
        parse.

        @if ($markdownUrl)
        Markdown version of this page: {{ $markdownUrl }}
        @endif

        You can also request this site with `Accept: text/markdown` to automatically get the Markdown version if available.

        Site-wide index for agents: {{ route('llms-txt') }}
    -->

    {{-- Anti-flash-of-wrong-theme: must run synchronously before any CSS/paint --}}
    <script>
        ;(() => {
            const stored = localStorage.getItem('theme') || 'system'
            const prefersDark = window.matchMedia(
                '(prefers-color-scheme: dark)'
            ).matches
            const isDark =
                stored === 'dark' || (stored === 'system' && prefersDark)

            document.documentElement.classList.toggle('dark', isDark)
        })()
    </script>

    @if ($noindex)
        <meta name="robots" content="noindex, nofollow" />
    @endif

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ $title }}</title>

    {{-- basic description for SEO/social; allow override via prop --}}
    <meta name="description" content="{{ $description }}" />

    <meta property="og:title" content="{{ $title }}" />
    <meta property="og:description" content="{{ $description }}" />

    <meta property="og:image" content="{{ asset('og-image.png') }}" />

    <x-json-ld
        :data="collect([
                    ['name' => '我的課表', 'url' => $scheduleNavHref],
                    ['name' => '學校公告', 'url' => route('announcements.index')],
                    ['name' => '連結 / 學習指導中心目錄', 'url' => route('directory.index')],
                    ['name' => '優惠店家', 'url' => route('discount-stores.index')],
                    ['name' => 'Alt UU', 'url' => route('alt-uu')],
                ])
                    ->map(fn ($item) => [
                        '@context' => 'https://schema.org',
                        '@type' => 'SiteNavigationElement',
                        'name' => $item['name'],
                        'url' => $item['url'],
                    ])
            ->all()"
    />

    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" />
    <link
        rel="icon"
        type="image/png"
        sizes="512x512"
        href="{{ asset('favicon.png') }}?v=2"
    />
    <link
        rel="icon"
        type="image/svg+xml"
        href="{{ asset('favicon.svg') }}?v=2"
    />

    {{--
            Styles / Scripts. Loaded before the Alpine CDN bundle below: both
            this module script and Alpine's deferred classic script execute
            in document order after parsing, so app.js's window.NouTime /
            window.nouGreeting are guaranteed to exist before Alpine
            evaluates any x-data that references them.
        --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    {{-- Alpine.js --}}
    <script
        defer
        src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"
    ></script>

    @if (app()->environment('production'))
        {{-- Google Analytics --}}
        <script
            async
            src="https://www.googletagmanager.com/gtag/js?id=G-1B65SQ4673"
        ></script>
        <script>
            window.dataLayer = window.dataLayer || []
            function gtag() {
                dataLayer.push(arguments)
            }
            gtag('js', new Date())

            gtag('config', 'G-1B65SQ4673', { send_page_view: false })
        </script>
    @endif

    @stack('head')
</head>
<body
    class="bg-warm-50 text-warm-900 dark:bg-zinc-950 dark:text-zinc-100"
    data-analytics-page="{{ $analyticsPage }}"
    data-analytics-title="{{ $analyticsTitle }}"
>
    <a
        href="#main-content"
        class="skip-link absolute top-auto -left-100 z-999 bg-transparent px-2 py-1 focus:top-0 focus:left-0 focus:bg-white focus:text-warm-900 focus:ring-2 focus:ring-warm-500 dark:focus:bg-zinc-900 dark:focus:text-zinc-100"
    >
        跳到主要區塊
    </a>

    <header
        class="sticky top-0 z-40 border-b border-warm-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 print:static"
    >
        <div
            x-data="{ open: false }"
            class="relative mx-auto max-w-7xl px-3 py-2 md:px-6 md:py-4"
        >
            <div class="flex items-center justify-between">
                <h1
                    class="inline-flex items-center gap-2 pb-0 text-lg font-bold text-warm-700 md:gap-4 md:text-2xl dark:text-zinc-300"
                >
                    <x-heroicon-o-book-open
                        class="size-5 shrink-0 text-warm-700 md:size-6 dark:text-zinc-300"
                    />
                    <a href="{{ url('/') }}" class="shrink-0"> NOU 小幫手 </a>
                </h1>

                <div class="flex items-center gap-2">
                    <div
                        x-data="{
                            theme: localStorage.getItem('theme') || 'system',
                            apply() {
                                const prefersDark = window.matchMedia(
                                    '(prefers-color-scheme: dark)'
                                ).matches
                                const isDark =
                                    this.theme === 'dark' ||
                                    (this.theme === 'system' && prefersDark)
                                document.documentElement.classList.toggle(
                                    'dark',
                                    isDark
                                )
                            },
                            cycle() {
                                this.theme =
                                    this.theme === 'system'
                                        ? 'light'
                                        : this.theme === 'light'
                                          ? 'dark'
                                          : 'system'
                                localStorage.setItem('theme', this.theme)
                                this.apply()
                            },
                            init() {
                                this.apply()
                                window
                                    .matchMedia('(prefers-color-scheme: dark)')
                                    .addEventListener('change', () => {
                                        if (this.theme === 'system') {
                                            this.apply()
                                        }
                                    })
                            },
                        }"
                        x-cloak
                    >
                        <button
                            type="button"
                            @click="cycle()"
                            class="inline-flex items-center justify-center rounded-md border border-warm-200 bg-white p-2 text-warm-700 transition hover:bg-warm-50 focus:ring-2 focus:ring-warm-500 focus:outline-none md:mr-2 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        >
                            <span class="sr-only">切換佈景主題</span>

                            <x-heroicon-o-sun
                                x-show="theme === 'light'"
                                class="size-5"
                            />
                            <x-heroicon-o-moon
                                x-show="theme === 'dark'"
                                class="size-5"
                            />
                            <x-heroicon-o-computer-desktop
                                x-show="theme === 'system'"
                                class="size-5"
                            />
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="open = !open"
                        :aria-expanded="open.toString()"
                        class="inline-flex items-center justify-center rounded-md border border-warm-200 bg-white p-2 text-warm-700 transition hover:bg-warm-50 focus:ring-2 focus:ring-warm-500 focus:outline-none md:hidden dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        <span class="sr-only">切換選單</span>

                        <x-heroicon-o-bars-3 x-show="!open" class="size-5" />

                        <x-heroicon-o-x-mark x-show="open" class="size-5" />
                    </button>

                    <nav class="hidden items-center gap-1 gap-x-6 md:flex">
                        <a
                            href="{{ $scheduleNavHref }}"
                            @class([
                                    '-m-2 inline-flex items-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium transition-colors md:px-3',
                                    'bg-warm-100 text-warm-900 dark:bg-warm-900/40 dark:text-warm-100' => str_starts_with(
                                        $routeName ?? '',
                                        'schedules',
                                    ),
                                    'text-warm-600 hover:bg-warm-100 hover:text-warm-900 dark:text-zinc-400 dark:hover:bg-warm-900/40 dark:hover:text-warm-100' => ! str_starts_with(
                                        $routeName ?? '',
                                        'schedules',
                                    ),
                                ])
                        >
                            <x-heroicon-o-table-cells class="size-4 shrink-0" />
                            <span class="hidden sm:inline">我的課表</span>
                        </a>

                        <a
                            href="{{ route('announcements.index') }}"
                            @class([
                                    '-m-2 inline-flex items-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium transition-colors md:px-3',
                                    'bg-warm-100 text-warm-900 dark:bg-warm-900/40 dark:text-warm-100' =>
                                        $routeName === 'announcements.index',
                                    'text-warm-600 hover:bg-warm-100 hover:text-warm-900 dark:text-zinc-400 dark:hover:bg-warm-900/40 dark:hover:text-warm-100' =>
                                        $routeName !== 'announcements.index',
                                ])
                        >
                            <x-heroicon-o-megaphone class="size-4 shrink-0" />
                            <span class="hidden sm:inline">學校公告</span>
                        </a>

                        <a
                            href="{{ route('directory.index') }}"
                            data-offline-allow
                            @class([
                                    '-m-2 inline-flex items-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium transition-colors md:px-3',
                                    'bg-warm-100 text-warm-900 dark:bg-warm-900/40 dark:text-warm-100' =>
                                        $routeName === 'directory.index',
                                    'text-warm-600 hover:bg-warm-100 hover:text-warm-900 dark:text-zinc-400 dark:hover:bg-warm-900/40 dark:hover:text-warm-100' =>
                                        $routeName !== 'directory.index',
                                ])
                        >
                            <x-heroicon-o-link class="size-4 shrink-0" />
                            <span class="hidden sm:inline">
                                連結 / 指導中心
                            </span>
                        </a>

                        <a
                            href="{{ route('discount-stores.index') }}"
                            @class([
                                    '-m-2 inline-flex items-center gap-1.5 rounded-md px-5 py-2 text-sm font-medium transition-colors md:px-3',
                                    'bg-warm-100 text-warm-900 dark:bg-warm-900/40 dark:text-warm-100' => str_starts_with(
                                        $routeName ?? '',
                                        'discount-stores',
                                    ),
                                    'text-warm-600 hover:bg-warm-100 hover:text-warm-900 dark:text-zinc-400 dark:hover:bg-warm-900/40 dark:hover:text-warm-100' => ! str_starts_with(
                                        $routeName ?? '',
                                        'discount-stores',
                                    ),
                                ])
                        >
                            <x-heroicon-o-tag class="size-4 shrink-0" />
                            <span class="hidden sm:inline">優惠店家</span>
                        </a>

                        <a
                            href="{{ route('alt-uu') }}"
                            @class([
                                    '-m-2 inline-flex items-center gap-1.5 rounded-md px-5 py-2 text-sm font-medium transition-colors md:px-3',
                                    'bg-warm-100 text-warm-900 dark:bg-warm-900/40 dark:text-warm-100' =>
                                        $routeName === 'alt-uu',
                                    'text-warm-600 hover:bg-warm-100 hover:text-warm-900 dark:text-zinc-400 dark:hover:bg-warm-900/40 dark:hover:text-warm-100' =>
                                        $routeName !== 'alt-uu',
                                ])
                        >
                            <x-heroicon-o-device-phone-mobile
                                class="size-4 shrink-0"
                            />
                            <span class="hidden sm:inline">Alt UU</span>
                        </a>
                    </nav>
                </div>
            </div>

            <div
                x-show="open"
                @click.outside="open = false"
                class="absolute top-full right-0 left-0 -mx-px mt-0 space-y-2 rounded-b-2xl border border-warm-200 bg-white p-3 shadow-lg md:hidden dark:border-zinc-700 dark:bg-zinc-900"
            >
                <a
                    href="{{ $scheduleNavHref }}"
                    @class([
                            'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            'bg-warm-100 text-warm-900 dark:bg-warm-900/40 dark:text-warm-100' => str_starts_with(
                                $routeName ?? '',
                                'schedules',
                            ),
                            'text-warm-600 hover:bg-warm-100 hover:text-warm-900 dark:text-zinc-400 dark:hover:bg-warm-900/40 dark:hover:text-warm-100' => ! str_starts_with(
                                $routeName ?? '',
                                'schedules',
                            ),
                        ])
                >
                    <x-heroicon-o-table-cells class="size-4 shrink-0" />
                    我的課表
                </a>

                <a
                    href="{{ route('announcements.index') }}"
                    @class([
                            'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            'bg-warm-100 text-warm-900 dark:bg-warm-900/40 dark:text-warm-100' =>
                                $routeName === 'announcements.index',
                            'text-warm-600 hover:bg-warm-100 hover:text-warm-900 dark:text-zinc-400 dark:hover:bg-warm-900/40 dark:hover:text-warm-100' =>
                                $routeName !== 'announcements.index',
                        ])
                >
                    <x-heroicon-o-megaphone class="size-4 shrink-0" />
                    學校公告
                </a>

                <a
                    href="{{ route('directory.index') }}"
                    data-offline-allow
                    @class([
                            'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            'bg-warm-100 text-warm-900 dark:bg-warm-900/40 dark:text-warm-100' =>
                                $routeName === 'directory.index',
                            'text-warm-600 hover:bg-warm-100 hover:text-warm-900 dark:text-zinc-400 dark:hover:bg-warm-900/40 dark:hover:text-warm-100' =>
                                $routeName !== 'directory.index',
                        ])
                >
                    <x-heroicon-o-link class="size-4 shrink-0" />
                    連結 / 指導中心
                </a>

                <a
                    href="{{ route('discount-stores.index') }}"
                    @class([
                            'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            'bg-warm-100 text-warm-900 dark:bg-warm-900/40 dark:text-warm-100' => str_starts_with(
                                $routeName ?? '',
                                'discount-stores',
                            ),
                            'text-warm-600 hover:bg-warm-100 hover:text-warm-900 dark:text-zinc-400 dark:hover:bg-warm-900/40 dark:hover:text-warm-100' => ! str_starts_with(
                                $routeName ?? '',
                                'discount-stores',
                            ),
                        ])
                >
                    <x-heroicon-o-tag class="size-4 shrink-0" />
                    優惠店家
                </a>

                <a
                    href="{{ route('alt-uu') }}"
                    @class([
                            'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            'bg-warm-100 text-warm-900 dark:bg-warm-900/40 dark:text-warm-100' =>
                                $routeName === 'alt-uu',
                            'text-warm-600 hover:bg-warm-100 hover:text-warm-900 dark:text-zinc-400 dark:hover:bg-warm-900/40 dark:hover:text-warm-100' =>
                                $routeName !== 'alt-uu',
                        ])
                >
                    <x-heroicon-o-device-phone-mobile class="size-4 shrink-0" />
                    Alt UU
                </a>
            </div>
        </div>
    </header>

    <main id="main-content" class="mx-auto max-w-7xl px-6 py-8">
        {{-- flash notifications use slide‑in toasts instead of the old alert box --}}
        @if (session('success'))
            <x-notification
                type="success"
                :message="session('success')"
                class="print:hidden"
            />
        @endif

        @if ($errors->any())
            {{-- show first error only in toast; the page can still display the full list if needed --}}
            <x-notification
                type="error"
                :message="$errors->first()"
                class="print:hidden"
            />
        @endif

        {{ $slot }}
    </main>

    <footer
        class="mt-12 border-t border-warm-200 bg-warm-100 py-8 text-warm-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 print:bg-white print:text-black"
    >
        <div class="mx-auto max-w-7xl px-6">
            <div
                class="hidden py-2 text-center text-xs text-warm-800 print:block"
            >
                <p class="mb-1">&copy; {{ date('Y') }} NOU 小幫手 — {{ url('/') }}
                <br />
                免責聲明：本網站為學生自發製作之工具，僅供參考，請以學校正式公告為準。</p>
                <p class="text-xs">網站原始碼：https://github.com/binotaliu/nou-tools
                <br />
                聯絡網站作者：nou-tools-contact@binota.org</p>
            </div>

            <div
                class="flex flex-col items-center justify-between gap-10 md:flex-row md:gap-6 print:hidden"
            >
                <div
                    class="flex flex-col items-center gap-1 md:flex-row md:gap-4"
                >
                    <div class="p-3">
                        <x-heroicon-o-book-open
                            class="size-6 text-warm-700 dark:text-zinc-300"
                        />
                    </div>

                    <div class="text-center md:text-left">
                        <a
                            href="{{ url('/') }}"
                            class="text-lg font-semibold text-warm-700 hover:text-warm-900 dark:text-zinc-300 dark:hover:text-zinc-100"
                        >
                            NOU 小幫手
                        </a>
                        <p
                            class="mt-1 text-xs text-warm-500 dark:text-zinc-400"
                        >給 NOU 同學的非官方小工具</p>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-6 sm:flex-row">
                    <div
                        class="max-w-lg text-center text-sm text-warm-400 md:text-left dark:text-zinc-500"
                    >
                        <span class="font-semibold">免責聲明：</span>
                        <p class="mb-2 text-justify text-xs md:text-left">本網站為學生自發製作之工具，僅供同學參考使用，並非學校官方發布；所有資訊以學校正式公告為準；本網站已盡可能提供準確資訊，但不保證其完整性或正確性；針對重要資訊，請使用者自行查證並以學校官方公告為準；課程相關資訊係搜集整理自學校官方公告、網站，與其他官方資料，採用合理使用原則提供同學參考使用；使用本網站即表示同意此免責聲明之內容。</p>
                        <span class="font-semibold">
                            開放原始碼授權聲明：
                        </span>
                        <p class="text-justify text-xs md:text-left">本網站是自由且開放原始碼之軟體，使用 AGPL-3.0 授權條款。歡迎各位同學自由審閱、修改、使用、再散佈本網站原始碼，但請遵守 AGPL 授權條款。如果您以任何形式參考了本網站之原始碼並開發了新的軟體，則此一沿伸軟體也必須使用與遵守 AGPL 授權條款，請在閱讀、參考、引用本網站原始碼時特別注意授權問題。</p>
                    </div>
                </div>
            </div>

            {{-- Bottom row (screen only) --}}
            <div
                class="mt-6 flex flex-col-reverse items-center justify-between gap-6 border-t border-warm-200 pt-4 text-xs text-warm-500 md:flex-row md:gap-3 dark:border-zinc-700 dark:text-zinc-400 print:hidden"
            >
                <div>&copy; {{ date('Y') }} NOU 小幫手</div>
                <div class="flex items-center gap-x-8 gap-y-2">
                    <div class="text-xs">
                        <a
                            href="https://github.com/binotaliu/nou-tools"
                            class="inline-flex items-center gap-1 text-warm-500 hover:text-warm-600 dark:text-zinc-400 dark:hover:text-zinc-300"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <x-heroicon-o-code-bracket class="size-3" />
                            網站原始碼
                        </a>
                    </div>
                    <div class="text-xs">
                        <a
                            href="mailto:nou-tools-contact@binota.org"
                            class="inline-flex items-center gap-1 text-warm-500 hover:text-warm-600 dark:text-zinc-400 dark:hover:text-zinc-300"
                        >
                            <x-heroicon-o-envelope class="size-3" />
                            聯絡作者
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    @if (app()->environment('production'))
        <script>
            ;(() => {
                if (typeof window.gtag !== 'function') {
                    return
                }

                const page = document.body?.dataset?.analyticsPage
                const title = document.body?.dataset?.analyticsTitle

                if (!page || !title) {
                    return
                }

                window.gtag('event', 'page_view', {
                    page_path: page,
                    page_title: title,
                    page_location: window.location.href,
                })
            })()
        </script>
    @endif
</body>
</html>
