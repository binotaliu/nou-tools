@php
    // Advertises the machine-readable Markdown twin of the current page
    // (see the *MarkdownController classes) when the route has one.
    $routeName = request()->route()?->getName();

    $markdownRouteName = match ($routeName) {
        'home' => 'llms-txt',
        default => $routeName ? $routeName.'.md' : null,
    };

    $markdownUrl = $markdownRouteName && \Illuminate\Support\Facades\Route::has($markdownRouteName)
        ? route($markdownRouteName, request()->route()->parameters())
        : null;

    // `$analyticsPage`: the matched route's URI with dynamic segments
    // masked out (e.g. `/schedules/{schedule}` -> `/schedules/:schedule`),
    // so per-page analytics group by route shape rather than by every
    // distinct schedule/course/etc. token.
    $currentRoute = request()->route();

    $analyticsPage = $currentRoute
        ? '/'.ltrim(preg_replace('/\{(\w+)\??\}/', ':$1', $currentRoute->uri()), '/')
        : '/'.ltrim(request()->path(), '/');

    // Errors raised inside a matched route (a 404 on articles.show, ...) keep
    // that route's name but render the Error page, which has none of the
    // props the per-route head views read.
    $isErrorPage = ($page['component'] ?? null) === 'Error';

    $ogImageView = $routeName && ! $isErrorPage && view()->exists('og-image.'.$routeName)
        ? 'og-image.'.$routeName
        : null;

    $seoView = $routeName && ! $isErrorPage && view()->exists('seo.'.$routeName)
        ? 'seo.'.$routeName
        : null;

    // The splash covers the blank gap before Vue mounts; the og:image
    // screenshot must never capture it.
    $showSplash = ! request()->has(config('og-image.preview_parameter', 'ogimage'));

    // Consent Mode v2 default: Taiwan is opt-out (granted unless the visitor
    // said otherwise), everywhere else is opt-in (denied by default). See
    // NouTools\Domains\Shared\Actions\ResolveAnalyticsConsent.
    $analyticsConsentGranted = app(\NouTools\Domains\Shared\Actions\ResolveAnalyticsConsent::class)(request())->state
        === \App\Enums\AnalyticsConsentState::Granted;
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
    <script @cspNonce>
        ;(() => {
            const stored = localStorage.getItem('theme') || 'system'
            const prefersDark = window.matchMedia(
                '(prefers-color-scheme: dark)'
            ).matches
            const isDark =
                stored === 'dark' || (stored === 'system' && prefersDark)

            document.documentElement.classList.toggle('dark', isDark)

            // Installed PWA (all browsers, incl. iOS's non-standard flag):
            // gates the launch splash below.
            if (
                window.matchMedia('(display-mode: standalone)').matches ||
                navigator.standalone === true
            ) {
                document.documentElement.dataset.pwa = ''

                // Tablet/desktop PWA nav style (useNavStyle): top tabs by
                // default, a sidebar when the reader chose one.
                if (localStorage.getItem('nou:nav-style:v1') === 'sidebar') {
                    document.documentElement.dataset.navStyle = 'sidebar'
                }
            }

            const accent = localStorage.getItem('accent-color')
            if (accent) {
                document.documentElement.dataset.accent = accent
            }

            if (localStorage.getItem('nou:reduce-motion:v1') === 'on') {
                document.documentElement.dataset.reduceMotion = 'true'
            }

            const fontSize = localStorage.getItem('font-size')
            if (fontSize) {
                document.documentElement.dataset.fontSize = fontSize
            }
        })()
    </script>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    {{-- The installed PWA's bottom tab bar needs env(safe-area-inset-bottom),
    which is only non-zero with viewport-fit=cover. Browser tabs keep the
    plain viewport so landscape notches don't clip content. Pinch-zoom is
    deliberately left enabled (WCAG 1.4.4). --}}
    <script @cspNonce>
        if (document.documentElement.hasAttribute('data-pwa')) {
            document
                .querySelector('meta[name="viewport"]')
                .setAttribute(
                    'content',
                    'width=device-width, initial-scale=1, viewport-fit=cover'
                )
        }
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    {{-- Read by resources/js/app.js so Inertia's progress-bar <style> tag can
    carry the same nonce our CSP (style-src with no 'unsafe-inline') requires,
    otherwise the browser silently drops that inline stylesheet. --}}
    <meta name="csp-nonce" content="{{ app('csp-nonce') }}" />

    {{-- Pages with a resources/views/seo/{route name}.blade.php get
    their own <title>, description, robots, Open Graph/Twitter tags and JSON-LD
    (see seo/_meta.blade.php); everything else falls back to this title. --}}
    @if ($seoView)
        @include($seoView, ['props' => $page['props']])
    @else
        <title inertia>NOU 小幫手</title>
    @endif

    {{-- Pages with a resources/views/og-image/{route name}.blade.php card get
    a generated og:image from spatie/laravel-og-image instead of the static one. --}}
    @unless ($ogImageView)
        <meta property="og:image" content="{{ asset('og-image.png') }}" />
    @endunless

    <link id="favicon-ico" rel="icon" href="{{ asset('favicon.ico') }}?v=2" />
    <link
        id="favicon-png"
        rel="icon"
        type="image/png"
        sizes="512x512"
        href="{{ asset('favicon.png') }}?v=2"
    />
    <link
        id="favicon-svg"
        rel="icon"
        type="image/svg+xml"
        href="{{ asset('favicon.svg') }}?v=2"
    />

    {{-- PWA: installability + iOS "Add to Home Screen" support --}}
    <link rel="manifest" href="{{ route('pwa.manifest') }}" />
    <meta name="theme-color" content="#b05139" />
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}?v=1" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-title" content="NOU 小幫手" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />

    @if (app()->environment('production'))
        {{-- Google Analytics --}}
        <script
            async
            src="https://www.googletagmanager.com/gtag/js?id=G-1B65SQ4673"
            @cspNonce
        ></script>
        <script @cspNonce>
            window.dataLayer = window.dataLayer || []
            function gtag() {
                dataLayer.push(arguments)
            }

            {{-- Consent Mode v2 default, set before the first config/event call per Google's required ordering. Only analytics_storage is meaningful here (no ads product is in use); the ad_* signals stay permanently denied. --}}
            gtag('consent', 'default', {
                ad_storage: 'denied',
                ad_user_data: 'denied',
                ad_personalization: 'denied',
                analytics_storage: '{{ $analyticsConsentGranted ? 'granted' : 'denied' }}',
            })

            gtag('js', new Date())

            {{-- page_location/referrer are masked here too so events firing before the first Inertia navigate (and GA's automatic ones) never carry the real schedule token; app.js keeps them updated per navigation. --}}
            gtag('config', 'G-1B65SQ4673', {
                send_page_view: false,
                page_location: location.origin + @js($analyticsPage),
                page_referrer:
                    document.referrer.indexOf(location.origin) === 0
                        ? ''
                        : document.referrer,
            })

            {{-- User properties for the theme signals computed above; each also needs a GA4 Admin custom dimension to show in reports. --}}
            gtag('set', 'user_properties', {
                display_mode: document.documentElement.hasAttribute('data-pwa')
                    ? 'pwa'
                    : 'browser',
                color_scheme: document.documentElement.classList.contains('dark')
                    ? 'dark'
                    : 'light',
                accent_color: document.documentElement.dataset.accent || 'default',
            })
        </script>
    @endif

    @if ($showSplash)
        {{-- Launch splash (installed PWA only — hidden unless the head script
        above sets html[data-pwa]): paints from the HTML alone (inline CSS, no
        external assets on the critical path), so a cold-started PWA shows
        something while the JS/CSS bundle loads instead of a blank screen.
        app.js removes #app-splash once Vue has mounted. --}}
        <style @cspNonce>
            html {
                --splash-hue: 40;
            }
            html[data-pwa] {
                background: oklch(0.98 0.01 var(--splash-hue));
            }
            html[data-accent='ocean'] {
                --splash-hue: 230;
            }
            html[data-accent='forest'] {
                --splash-hue: 150;
            }
            html[data-accent='purple'] {
                --splash-hue: 300;
            }
            html[data-pwa].dark {
                background: oklch(0.141 0.005 285.823);
            }
            #app-splash {
                display: none;
                position: fixed;
                inset: 0;
                z-index: 9999;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 1rem;
                background: oklch(0.98 0.01 var(--splash-hue));
                color: oklch(0.55 0.13 var(--splash-hue));
                font-family: system-ui, sans-serif;
                font-size: 1.125rem;
                font-weight: 700;
                transition: opacity 0.2s ease-out;
            }
            html[data-pwa] #app-splash {
                display: flex;
            }
            html.dark #app-splash {
                background: oklch(0.141 0.005 285.823);
                color: oklch(0.967 0.001 286.375);
            }
            #app-splash.is-done {
                opacity: 0;
                pointer-events: none;
            }
            #app-splash img {
                width: 5rem;
                height: 5rem;
                border-radius: 1.25rem;
            }
            #app-splash .spinner {
                width: 1.5rem;
                height: 1.5rem;
                border: 3px solid currentColor;
                border-top-color: transparent;
                border-radius: 50%;
                opacity: 0.6;
                animation: app-splash-spin 0.8s linear infinite;
            }
            @keyframes app-splash-spin {
                to {
                    transform: rotate(360deg);
                }
            }
            @media (prefers-reduced-motion: reduce) {
                #app-splash .spinner {
                    animation-duration: 2.4s;
                }
            }
        </style>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="bg-theme-50 text-theme-900 dark:bg-zinc-950 dark:text-zinc-100"
    data-analytics-page="{{ $analyticsPage }}"
>
    @if ($showSplash)
        <div id="app-splash" role="status" aria-live="polite">
            <img
                src="{{ asset('icons/icon-192.png') }}?v=1"
                alt=""
                width="80"
                height="80"
            />
            <span>NOU 小幫手</span>
            <span class="spinner" aria-hidden="true"></span>
        </div>
    @endif
    @inertia
    <noscript>
        <div
            class="fixed inset-0 z-9999 flex flex-col items-center justify-center gap-4 bg-theme-50 p-8 text-center text-theme-900"
        >
            <strong class="text-xl">NOU 小幫手需要 JavaScript</strong>
            <span
                >請在瀏覽器設定中啟用 JavaScript
                後重新整理頁面，才能使用本網站。</span
            >
            <span
                >不喜歡 JavaScript？請參考
                <a href="{{ route('llms-txt') }}" class="underline"
                    >Markdown 版本</a
                >。</span
            >
            <span
                >作者有計劃要提供無 JavaScript 的簡單 HTML
                版本，但目前還沒有時間做。如果你想，可以<a
                    href="https://github.com/binotaliu/nou-tools"
                    class="underline"
                    >前往 GitHub 幫忙開發</a
                >。</span
            >
        </div>
    </noscript>
    @if ($ogImageView)
        @include($ogImageView, ['props' => $page['props']])
    @endif
</body>
</html>
