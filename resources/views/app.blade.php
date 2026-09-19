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

    $ogImageView = $routeName && view()->exists('og-image.'.$routeName)
        ? 'og-image.'.$routeName
        : null;
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

            const accent = localStorage.getItem('accent-color')
            if (accent) {
                document.documentElement.dataset.accent = accent
            }
        })()
    </script>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    {{-- Read by resources/js/app.js so Inertia's progress-bar <style> tag can
    carry the same nonce our CSP (style-src with no 'unsafe-inline') requires,
    otherwise the browser silently drops that inline stylesheet. --}}
    <meta name="csp-nonce" content="{{ app('csp-nonce') }}" />

    <title inertia>NOU 小幫手</title>

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
            gtag('js', new Date())

            gtag('config', 'G-1B65SQ4673', { send_page_view: false })
        </script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="bg-theme-50 text-theme-900 dark:bg-zinc-950 dark:text-zinc-100"
    data-analytics-page="{{ $analyticsPage }}"
>
    @inertia
    @if ($ogImageView)
        @include($ogImageView, ['props' => $page['props']])
    @endif
</body>
</html>
