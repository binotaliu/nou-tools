@props([
    'title' => 'NOU 小幫手',
    'description' => '給 NOU 同學的非官方小工具：管理個人課表與學習進度',
    'noindex' => false,
])

@inject('readScheduleCookie', 'NouTools\Domains\Schedules\Actions\ReadStudentScheduleCookie')

@php
    $routeName = request()
        ->route()
        ?->getName();

    $scheduleFromCookie = $readScheduleCookie(request());
    $scheduleNavHref = $scheduleFromCookie
        ? route('schedules.show', $scheduleFromCookie->token)
        : route('schedules.create');

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

        {{-- Alpine.js --}}
        <script
            defer
            src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"
        ></script>

        {{-- Styles / Scripts --}}
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

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
    </head>
    <body
        class="bg-warm-50 text-warm-900"
        data-analytics-page="{{ $analyticsPage }}"
        data-analytics-title="{{ $analyticsTitle }}"
    >
        <a
            href="#main-content"
            class="skip-link absolute top-auto -left-100 z-999 bg-transparent px-2 py-1 focus:top-0 focus:left-0 focus:bg-white focus:text-warm-900 focus:ring-2 focus:ring-warm-500"
        >
            跳到主要區塊
        </a>

        <header
            class="sticky top-0 z-40 border-b border-warm-200 bg-white print:static"
        >
            <div
                x-data="{ open: false }"
                class="relative mx-auto max-w-7xl px-3 py-2 md:px-6 md:py-4"
            >
                <div class="flex items-center justify-between">
                    <h1
                        class="inline-flex items-center gap-2 pb-0 text-lg font-bold text-warm-700 md:gap-4 md:text-2xl"
                    >
                        <x-heroicon-o-book-open
                            class="size-5 shrink-0 text-warm-700 md:size-6"
                        />
                        <a href="{{ url('/') }}" class="shrink-0">
                            NOU 小幫手
                        </a>
                    </h1>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="open = !open"
                            :aria-expanded="open.toString()"
                            class="inline-flex items-center justify-center rounded-md border border-warm-200 bg-white p-2 text-warm-700 transition hover:bg-warm-50 focus:ring-2 focus:ring-warm-500 focus:outline-none md:hidden"
                        >
                            <span class="sr-only">切換選單</span>

                            <x-heroicon-o-bars-3
                                x-show="!open"
                                class="size-5"
                            />

                            <x-heroicon-o-x-mark x-show="open" class="size-5" />
                        </button>

                        <nav class="hidden items-center gap-1 gap-x-6 md:flex">
                            <a
                                href="{{ $scheduleNavHref }}"
                                @class([
                                    '-m-2 inline-flex items-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium transition-colors md:px-3',
                                    'bg-warm-100 text-warm-900' => str_starts_with(
                                        $routeName ?? '',
                                        'schedules',
                                    ),
                                    'text-warm-600 hover:bg-warm-100 hover:text-warm-900' => ! str_starts_with(
                                        $routeName ?? '',
                                        'schedules',
                                    ),
                                ])
                            >
                                <x-heroicon-o-table-cells
                                    class="size-4 shrink-0"
                                />
                                <span class="hidden sm:inline">我的課表</span>
                            </a>

                            <a
                                href="{{ route('alt-uu') }}"
                                @class([
                                    '-m-2 inline-flex items-center gap-1.5 rounded-md px-5 py-2 text-sm font-medium transition-colors md:px-3',
                                    'bg-warm-100 text-warm-900' => $routeName === 'alt-uu',
                                    'text-warm-600 hover:bg-warm-100 hover:text-warm-900' =>
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
                    class="absolute top-full right-0 left-0 -mx-px mt-0 space-y-2 rounded-b-2xl border border-warm-200 bg-white p-3 shadow-lg md:hidden"
                >
                    <a
                        href="{{ $scheduleNavHref }}"
                        @class([
                            'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            'bg-warm-100 text-warm-900' => str_starts_with(
                                $routeName ?? '',
                                'schedules',
                            ),
                            'text-warm-600 hover:bg-warm-100 hover:text-warm-900' => ! str_starts_with(
                                $routeName ?? '',
                                'schedules',
                            ),
                        ])
                    >
                        <x-heroicon-o-table-cells class="size-4 shrink-0" />
                        我的課表
                    </a>

                    <a
                        href="{{ route('alt-uu') }}"
                        @class([
                            'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            'bg-warm-100 text-warm-900' => $routeName === 'alt-uu',
                            'text-warm-600 hover:bg-warm-100 hover:text-warm-900' =>
                                $routeName !== 'alt-uu',
                        ])
                    >
                        <x-heroicon-o-device-phone-mobile
                            class="size-4 shrink-0"
                        />
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
            class="mt-12 border-t border-warm-200 bg-warm-100 py-8 text-warm-900 print:bg-white print:text-black"
        >
            <div class="mx-auto max-w-7xl px-6">
                <div
                    class="hidden py-2 text-center text-xs text-warm-800 print:block"
                >
                    <p class="mb-1">
                        &copy; {{ date('Y') }} NOU 小幫手 — {{ url('/') }}
                        <br />
                        免責聲明：本網站為學生自發製作之工具，僅供參考，請以學校正式公告為準。
                    </p>
                    <p class="text-xs">
                        網站原始碼：https://github.com/binotaliu/nou-tools
                        <br />
                        聯絡網站作者：nou-tools-contact@binota.org
                    </p>
                </div>

                <div
                    class="flex flex-col items-center justify-between gap-10 md:flex-row md:gap-6 print:hidden"
                >
                    <div
                        class="flex flex-col items-center gap-1 md:flex-row md:gap-4"
                    >
                        <div class="p-3">
                            <x-heroicon-o-book-open
                                class="size-6 text-warm-700"
                            />
                        </div>

                        <div class="text-center md:text-left">
                            <a
                                href="{{ url('/') }}"
                                class="text-lg font-semibold text-warm-700 hover:text-warm-900"
                            >
                                NOU 小幫手
                            </a>
                            <p class="mt-1 text-xs text-warm-500">
                                給 NOU 同學的非官方小工具
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-6 sm:flex-row">
                        <div
                            class="max-w-lg text-center text-sm text-warm-400 md:text-left"
                        >
                            <span class="font-semibold">免責聲明：</span>
                            <p class="text-justify text-xs md:text-left">
                                本網站為學生自發製作之工具，僅供同學參考使用，並非學校官方發布；所有資訊以學校正式公告為準；本網站已盡可能提供準確資訊，但不保證其完整性或正確性；針對重要資訊，請使用者自行查證並以學校官方公告為準；課程相關資訊係搜集整理自學校官方公告、網站，與其他官方資料，採用合理使用原則提供同學參考使用；使用本網站即表示同意此免責聲明之內容。
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Bottom row (screen only) --}}
                <div
                    class="mt-6 flex flex-col-reverse items-center justify-between gap-6 border-t border-warm-200 pt-4 text-xs text-warm-500 md:flex-row md:gap-3 print:hidden"
                >
                    <div>&copy; {{ date('Y') }} NOU 小幫手</div>
                    <div class="flex items-center gap-x-8 gap-y-2">
                        <div class="text-xs">
                            <a
                                href="https://github.com/binotaliu/nou-tools"
                                class="inline-flex items-center gap-1 text-warm-500 hover:text-warm-600"
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
                                class="inline-flex items-center gap-1 text-warm-500 hover:text-warm-600"
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
