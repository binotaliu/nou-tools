@extends('layouts.lite')

@section('title', '目前無法連線')

@section('content')
    <div class="card">
        <h2>目前無法連線</h2>
        <p>這個頁面暫時無法顯示，可能是你沒有網路，也可能是我們的伺服器正在維護。</p>
        <p id="schedule-status">課表頁面會在你開啟時自動備份，之後即使連不上伺服器也能檢視課表與視訊上課連結。</p>
        <ul id="schedule-links" class="links" hidden></ul>

        <div class="buttons">
            <button type="button" class="button" id="retry-button">
                重新整理
            </button>
            <a class="button primary" href="{{ url('/') }}">回到首頁</a>
        </div>
    </div>
@endsection

@push('scripts')
    <script @cspNonce>
        document
            .getElementById('retry-button')
            .addEventListener('click', () => location.reload())

        // Lists the schedule backups /sw.js saved (see LITE_PATTERN there),
        // labelled with each backup's own <title>. When there are none the
        // default text explains how to get one.
        ;(async () => {
            if (!('caches' in window)) {
                return
            }

            try {
                const cacheNames = (await caches.keys()).filter(name =>
                    name.startsWith('nou-lite-')
                )
                const paths = new Set()

                for (const name of cacheNames) {
                    const cache = await caches.open(name)

                    for (const request of await cache.keys()) {
                        const { pathname } = new URL(request.url)

                        if (/^\/schedules\/[^/]+\/lite$/.test(pathname)) {
                            paths.add(pathname)
                        }
                    }
                }

                if (paths.size === 0) {
                    return
                }

                const listEl = document.getElementById('schedule-links')

                document.getElementById('schedule-status').textContent =
                    '你可以檢視這些已備份的課表：'
                listEl.hidden = false

                for (const pathname of paths) {
                    const item = document.createElement('li')
                    const link = document.createElement('a')
                    let label = pathname

                    try {
                        const html = await (await caches.match(pathname)).text()
                        const title = new DOMParser()
                            .parseFromString(html, 'text/html')
                            .querySelector('title')
                            ?.textContent?.trim()

                        label =
                            title?.replace(/\s*-\s*NOU 小幫手\s*$/, '') || label
                    } catch (error) {}

                    link.className = 'button'
                    link.href = pathname
                    link.textContent = label
                    item.appendChild(link)
                    listEl.appendChild(item)
                }
            } catch (error) {
                // Leave the default message in place.
            }
        })()
    </script>
@endpush
