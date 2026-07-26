<!DOCTYPE html>
<html lang="zh-hant">
<head>
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

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />

    <title>目前離線 - NOU 小幫手</title>

    {{--
            This page must render correctly with zero network access, so
            (like errors/minimal.blade.php) it does not use <x-layout> or
            @vite — both may depend on assets that were never cached.
        --}}
    <style>
        :root,
        :host {
            --color-white: #fff;
            --color-warm-50: oklch(0.98 0.01 40);
            --color-warm-200: oklch(0.93 0.04 40);
            --color-warm-600: oklch(0.65 0.15 35);
            --color-warm-700: oklch(0.55 0.13 35);
            --color-warm-900: oklch(0.35 0.08 35);
            --color-zinc-100: oklch(0.967 0.001 286.375);
            --color-zinc-300: oklch(0.871 0.006 286.286);
            --color-zinc-400: oklch(0.705 0.015 286.067);
            --color-zinc-700: oklch(0.37 0.013 285.805);
            --color-zinc-900: oklch(0.21 0.006 285.885);
            --color-zinc-950: oklch(0.141 0.005 285.823);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, sans-serif;
            background: var(--color-warm-50);
            color: var(--color-warm-900);
        }

        header {
            position: sticky;
            top: 0;
            border-bottom: 1px solid var(--color-warm-200);
            background: var(--color-white);
            padding: 0.5rem 0.75rem;
        }

        header h1 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--color-warm-700);
        }

        header a {
            color: inherit;
            text-decoration: none;
        }

        main {
            max-width: 28rem;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            display: flex;
            min-height: 60vh;
            align-items: center;
            justify-content: center;
        }

        .card {
            width: 100%;
            border: 1px solid var(--color-warm-200);
            border-radius: 0.5rem;
            background: var(--color-white);
            padding: 2rem;
            text-align: center;
        }

        .card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-warm-900);
            margin-bottom: 0.75rem;
        }

        .card p {
            color: var(--color-warm-900);
            line-height: 1.6;
        }

        .card p + p {
            margin-top: 0.75rem;
        }

        .actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 0.75rem;
        }

        .actions a,
        .actions button {
            flex: 1;
            display: block;
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            font: inherit;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }

        .actions button {
            border: 1px solid var(--color-warm-200);
            background: transparent;
            color: var(--color-warm-700);
        }

        .actions a {
            border: 0;
            background: var(--color-warm-600);
            color: var(--color-white);
        }

        .schedule-links {
            margin-top: 0.75rem;
            list-style: none;
            text-align: left;
        }

        .schedule-links li + li {
            margin-top: 0.5rem;
        }

        .schedule-links a {
            display: block;
            border: 1px solid var(--color-warm-200);
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            color: var(--color-warm-700);
            text-decoration: none;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: var(--color-zinc-950);
                color: var(--color-zinc-100);
            }

            header {
                border-color: var(--color-zinc-700);
                background: var(--color-zinc-900);
            }

            header h1 {
                color: var(--color-zinc-300);
            }

            .card {
                border-color: var(--color-zinc-700);
                background: var(--color-zinc-900);
            }

            .card h2,
            .card p {
                color: var(--color-zinc-100);
            }

            .actions button {
                border-color: var(--color-zinc-700);
                color: var(--color-zinc-300);
            }

            .schedule-links a {
                border-color: var(--color-zinc-700);
                color: var(--color-zinc-300);
            }
        }

        html.dark body {
            background: var(--color-zinc-950);
            color: var(--color-zinc-100);
        }

        html.dark header {
            border-color: var(--color-zinc-700);
            background: var(--color-zinc-900);
        }

        html.dark header h1 {
            color: var(--color-zinc-300);
        }

        html.dark .card {
            border-color: var(--color-zinc-700);
            background: var(--color-zinc-900);
        }

        html.dark .card h2,
        html.dark .card p {
            color: var(--color-zinc-100);
        }

        html.dark .actions button {
            border-color: var(--color-zinc-700);
            color: var(--color-zinc-300);
        }

        html.dark .schedule-links a {
            border-color: var(--color-zinc-700);
            color: var(--color-zinc-300);
        }
    </style>
</head>
<body>
    <header>
        <h1>
            <a href="{{ url('/') }}">NOU 小幫手</a>
        </h1>
    </header>

    <main>
        <div class="card">
            <h2>目前處於離線狀態</h2>
            <p>這個頁面沒有離線快取，因此暫時無法顯示。</p>
            <p id="schedule-status">離線時，你可以檢視曾經開啟過的課表頁面，並使用該頁面上的視訊上課連結。回到有網路連線的地方後即可正常瀏覽所有功能。</p>
            <ul id="schedule-links" class="schedule-links" hidden></ul>

            <div class="actions">
                <button onclick="history.back()">回到上一頁</button>
                <a href="{{ url('/') }}">回到首頁</a>
            </div>
        </div>
    </main>

    <script>
        // Looks through the Cache API (populated by /sw.js) for any
        // previously-visited /schedules/{token} pages, so this fallback
        // can tell the visitor whether they actually have anything
        // available to view offline, rather than always pointing them
        // at schedules that were never cached.
        ;(async () => {
            const statusEl = document.getElementById('schedule-status')
            const listEl = document.getElementById('schedule-links')

            if (!('caches' in window)) {
                return
            }

            try {
                const cacheNames = await caches.keys()
                const scheduleUrls = new Set()

                for (const name of cacheNames) {
                    const cache = await caches.open(name)
                    const requests = await cache.keys()

                    for (const request of requests) {
                        const { pathname } = new URL(request.url)
                        const match = /^\/schedules\/([^/]+)$/.exec(pathname)

                        if (match && match[1] !== 'create') {
                            scheduleUrls.add(pathname)
                        }
                    }
                }

                if (scheduleUrls.size === 0) {
                    statusEl.textContent =
                        '目前沒有任何已快取的課表頁面，因此無法在離線時檢視課表或視訊上課連結。請連上網路後開啟一次課表頁面，之後即可在離線時使用。'

                    return
                }

                statusEl.textContent =
                    '離線時，你可以檢視以下曾經開啟過的課表頁面，並使用頁面上的視訊上課連結：'

                listEl.hidden = false

                for (const pathname of scheduleUrls) {
                    const item = document.createElement('li')
                    const link = document.createElement('a')

                    link.href = pathname
                    link.textContent = pathname
                    item.appendChild(link)
                    listEl.appendChild(item)
                }
            } catch (error) {
                // Leave the default message in place.
            }
        })()
    </script>
</body>
</html>
