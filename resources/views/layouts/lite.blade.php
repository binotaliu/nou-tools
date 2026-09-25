<!DOCTYPE html>
<html lang="zh-hant">
<head>
    {{-- Anti-flash-of-wrong-theme: must run synchronously before any CSS/paint --}}
    <script @cspNonce>
        ;(() => {
            try {
                const stored = localStorage.getItem('theme') || 'system'
                const prefersDark = window.matchMedia(
                    '(prefers-color-scheme: dark)'
                ).matches

                document.documentElement.classList.toggle(
                    'dark',
                    stored === 'dark' || (stored === 'system' && prefersDark)
                )
            } catch (error) {}
        })()
    </script>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />

    <title>
        @yield('title')
        - NOU 小幫手
    </title>

    {{--
        The emergency ("lite") pages must render correctly with zero help
        from the app: no @vite, no Inertia, no external fonts or images, so
        a copy cached by /sw.js keeps working after later deploys replace
        the hashed build assets. Keep it that way.
    --}}
    <style @cspNonce>
        :root {
            --bg: oklch(0.98 0.01 40);
            --surface: #fff;
            --border: oklch(0.93 0.04 40);
            --text: oklch(0.35 0.08 35);
            --muted: oklch(0.5 0.06 35);
            --accent: oklch(0.65 0.15 35);
            --accent-text: oklch(0.55 0.13 35);
            --warn-bg: oklch(0.96 0.06 90);
            --warn-border: oklch(0.85 0.12 90);
            --warn-text: oklch(0.4 0.09 70);
        }

        html.dark {
            --bg: oklch(0.141 0.005 285.823);
            --surface: oklch(0.21 0.006 285.885);
            --border: oklch(0.37 0.013 285.805);
            --text: oklch(0.967 0.001 286.375);
            --muted: oklch(0.705 0.015 286.067);
            --accent: oklch(0.65 0.15 35);
            --accent-text: oklch(0.871 0.006 286.286);
            --warn-bg: oklch(0.28 0.05 80);
            --warn-border: oklch(0.45 0.09 80);
            --warn-text: oklch(0.92 0.06 90);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, sans-serif;
            line-height: 1.6;
            background: var(--bg);
            color: var(--text);
        }

        header {
            border-bottom: 1px solid var(--border);
            background: var(--surface);
            padding: 0.5rem 0.75rem;
        }

        header a {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--accent-text);
            text-decoration: none;
        }

        main {
            max-width: 40rem;
            margin: 0 auto;
            padding: 1rem;
        }

        h2 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .meta {
            color: var(--muted);
            font-size: 0.875rem;
        }

        .banner {
            margin-bottom: 1rem;
            border: 1px solid var(--warn-border);
            border-radius: 0.5rem;
            background: var(--warn-bg);
            color: var(--warn-text);
            padding: 0.75rem 1rem;
        }

        .card {
            margin-top: 1rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            background: var(--surface);
            padding: 1rem;
        }

        .card h3 {
            font-size: 1.125rem;
        }

        .card p {
            margin-top: 0.25rem;
        }

        .buttons {
            margin-top: 0.75rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .button {
            display: inline-block;
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            background: transparent;
            color: var(--accent-text);
            padding: 0.5rem 1rem;
            font: inherit;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
        }

        .button.primary {
            border-color: var(--accent);
            background: var(--accent);
            color: #fff;
        }

        details {
            margin-top: 0.75rem;
        }

        summary {
            cursor: pointer;
            color: var(--muted);
            font-size: 0.875rem;
        }

        ul.dates {
            margin-top: 0.5rem;
            list-style: none;
            font-size: 0.875rem;
            font-variant-numeric: tabular-nums;
        }

        ul.dates li.past {
            opacity: 0.5;
        }

        ul.links {
            margin-top: 0.75rem;
            list-style: none;
        }

        ul.links li + li {
            margin-top: 0.5rem;
        }

        ul.links a {
            display: block;
        }

        [hidden] {
            display: none !important;
        }
    </style>
</head>
<body>
    <header>
        <a href="{{ url('/') }}">NOU 小幫手</a>
    </header>

    <main>
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
