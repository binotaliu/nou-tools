// Emergency mode. Pages are Inertia+Vue, which can't run without the app's
// hashed build assets and live requests, so instead of caching them this
// worker keeps a small server-rendered "lite" copy of the visitor's schedule
// (GET /schedules/{token}/lite: plain Blade, inline CSS, the video-class
// links) fresh in the background. When a navigation can't reach the app —
// offline, or a gateway/outage response — that copy is served in its place,
// or the generic /offline page when there isn't one yet.
const CACHE_VERSION = 'v8'
const PAGE_CACHE = `nou-pages-${CACHE_VERSION}`
const RUNTIME_CACHE = `nou-runtime-${CACHE_VERSION}`
// The /offline page lists backups by scanning caches named `nou-lite-*`.
const LITE_CACHE = `nou-lite-${CACHE_VERSION}`

// /schedules/{token} and /schedules/{token}/lite — not /schedules/create or
// other nested routes.
const SCHEDULE_PATH_PATTERN = /^\/schedules\/([^/]+)(\/lite)?$/
const SCHEDULE_PDF_PATTERN = /^\/schedules\/[^/]+\/print\.pdf$/

// Which token was backed up last, stored as a tiny cache entry.
const LAST_TOKEN_KEY = '/__lite-last-token'

// Generic fallback shown when there's no schedule backup to serve. Precached
// so it's always available, even if the visitor never opened it directly.
const OFFLINE_URL = '/offline'

function liteUrl(token) {
  return `/schedules/${token}/lite`
}

function scheduleTokenFromUrl(url) {
  if (url.origin !== self.location.origin) {
    return null
  }

  const match = SCHEDULE_PATH_PATTERN.exec(url.pathname)

  return match && match[1] !== 'create' && match[1] !== 'my' ? match[1] : null
}

// Third-party origins whose assets are safe to cache for offline rendering
// (currently just Twemoji, loaded on demand by the study room — see
// resources/js/Pages/StudyRoom/Show.vue).
function isCacheableCrossOrigin(url) {
  return url.hostname === 'cdn.jsdelivr.net'
}

// 500 (the app is up but broken, e.g. the database is down), the standard
// gateway errors and Cloudflare's origin-error range (520-527): these mean
// the origin is unusable, not that the app returned a meaningful response. A real 401/403/404/429/etc from the app — including
// a Cloudflare bot-challenge page, which also answers 403 — must NOT match
// here, or we'd hide it behind the offline page and trap the visitor.
function isOutageStatus(status) {
  return (
    status === 500 ||
    status === 502 ||
    status === 503 ||
    status === 504 ||
    (status >= 520 && status <= 527)
  )
}

self.addEventListener('install', event => {
  event.waitUntil(
    caches
      .open(PAGE_CACHE)
      .then(cache => cache.add(OFFLINE_URL))
      .catch(() => {})
  )

  self.skipWaiting()
})

self.addEventListener('activate', event => {
  event.waitUntil(
    caches
      .keys()
      .then(keys =>
        Promise.all(
          keys
            .filter(
              key =>
                key !== PAGE_CACHE &&
                key !== RUNTIME_CACHE &&
                key !== LITE_CACHE
            )
            .map(key => caches.delete(key))
        )
      )
      .then(() => self.clients.claim())
  )
})

// Fetches the lite copy of a schedule and remembers it as the latest one.
// Failures are ignored: the previous copy simply stays.
async function refreshLite(token) {
  try {
    const response = await fetch(liteUrl(token), { cache: 'no-store' })

    if (!response || !response.ok) {
      return
    }

    const cache = await caches.open(LITE_CACHE)

    await cache.put(liteUrl(token), response)
    await cache.put(LAST_TOKEN_KEY, new Response(token))
  } catch (error) {}
}

// The same page with `data-emergency` on <html>, so the lite page knows it
// is standing in for something (it can't tell from its own headers, and the
// app's /up check stays green when only the database is down).
async function flagged(response) {
  const html = await response.text()

  return new Response(html.replace('<html ', '<html data-emergency '), {
    status: response.status,
    statusText: response.statusText,
    headers: response.headers,
  })
}

// The lite copy for the schedule the request is about, else the most
// recently backed-up one. Without any, a real response from the app (a 500
// page) is better than a generic message and passes through; a failed fetch
// or gateway error gets the generic /offline page.
async function emergencyResponse(url, original) {
  const lite = await caches.open(LITE_CACHE)
  const requested = scheduleTokenFromUrl(url)
  let backup = requested ? await lite.match(liteUrl(requested)) : undefined

  if (!backup) {
    const last = await lite.match(LAST_TOKEN_KEY)

    backup = last ? await lite.match(liteUrl(await last.text())) : undefined
  }

  if (backup) {
    return flagged(backup)
  }

  if (original && original.status === 500) {
    return original
  }

  const pages = await caches.open(PAGE_CACHE)

  return (await pages.match(OFFLINE_URL)) || original || Response.error()
}

async function staleWhileRevalidate(request) {
  const cache = await caches.open(RUNTIME_CACHE)
  const cached = await cache.match(request)

  const networkFetch = fetch(request)
    .then(response => {
      if (response && (response.ok || response.type === 'opaque')) {
        cache.put(request, response.clone())
      }

      return response
    })
    .catch(() => undefined)

  return cached || (await networkFetch) || Response.error()
}

// Requests made by the page's scripts for JSON (axios sends
// `Accept: application/json, ...`), as opposed to documents and assets.
function isJsonRequest(request) {
  const accept = request.headers.get('accept') || ''

  return accept.startsWith('application/json')
}

// Audio/video element loads, plus any ranged request (seeking).
function isMediaRequest(request) {
  return (
    request.destination === 'audio' ||
    request.destination === 'video' ||
    request.headers.has('range')
  )
}

// Inertia's own client-side page visits (<Link>, router.visit — used for
// every in-app navigation since the Blade+Alpine → Inertia+Vue migration).
// These are `fetch()` calls, so `request.mode` is never 'navigate' (only a
// real browser-initiated document load gets that), and their `Accept` is
// `text/html, application/xhtml+xml`, not `application/json` — so without
// this check they fall through to the generic same-origin
// staleWhileRevalidate bucket below, meant for JS/CSS/image assets. Serving
// one of those stale hands back a stale `X-Inertia-Version` header too,
// which silently defeats Inertia's own version-mismatch reload (it never
// sees the fresh response that would have triggered it). Treat these as live
// data like JSON: always network, never cached.
function isInertiaRequest(request) {
  return request.headers.get('X-Inertia') === 'true'
}

self.addEventListener('fetch', event => {
  const { request } = event

  if (request.method !== 'GET') {
    return
  }

  const url = new URL(request.url)

  // The client's connectivity probe (see app.js) hits this route to decide
  // whether it's actually offline. Leave it alone entirely — it must always
  // go straight to the network, uncached, or the probe would lie once
  // offline.
  if (url.origin === self.location.origin && url.pathname === '/up') {
    return
  }

  // JSON fetched by the page's own scripts (the 自習室's live room state,
  // a student's session log, ...) is live data, never something to serve
  // stale: a stale-while-revalidate answer here hands the page the
  // *previous* response and the room silently snaps back to an older
  // state. Pass it straight through to the network.
  if (isJsonRequest(request)) {
    return
  }

  if (isInertiaRequest(request)) {
    // In-app visits to a schedule page are our cue to refresh its backup
    // (there's no real navigation to hook when the app is already open).
    const token = scheduleTokenFromUrl(url)

    if (token && !url.pathname.endsWith('/lite')) {
      event.waitUntil(refreshLite(token))
    }

    return
  }

  // The schedule's PDF is fetched by the page in an installed PWA to hand to
  // the share sheet. It reflects the schedule as it is now (the server caches
  // it by content), so the stale-while-revalidate bucket below would share
  // the previous PDF after an edit.
  if (
    url.origin === self.location.origin &&
    SCHEDULE_PDF_PATTERN.test(url.pathname)
  ) {
    return
  }

  // Streamed media (the study room's cassette player). <audio> asks for
  // `Range: bytes=0-` and gets a 206, which cache.put() rejects, so the
  // stale-while-revalidate bucket below would turn playback and seeking into
  // errors. Let the browser talk to the network directly.
  if (isMediaRequest(request)) {
    return
  }

  // Navigations: network first for every same-origin page. A failed fetch
  // (offline) or a gateway/outage response (e.g. Cloudflare's 5xx page when
  // the origin is down, Laravel's 503 maintenance mode, or a 500 because the
  // database is down) is answered with
  // the emergency page instead. A real app response — including a 401/403/404
  // or a Cloudflare bot-challenge page — is passed through untouched.
  if (request.mode === 'navigate') {
    if (url.origin === self.location.origin && url.pathname !== OFFLINE_URL) {
      event.respondWith(
        fetch(request)
          .then(response => {
            if (response && isOutageStatus(response.status)) {
              return emergencyResponse(url, response)
            }

            const token = scheduleTokenFromUrl(url)

            if (
              token &&
              response &&
              response.ok &&
              !url.pathname.endsWith('/lite')
            ) {
              event.waitUntil(refreshLite(token))
            }

            return response
          })
          .catch(() => emergencyResponse(url))
      )
    }

    return
  }

  if (url.origin === self.location.origin || isCacheableCrossOrigin(url)) {
    event.respondWith(staleWhileRevalidate(request))
  }
})

self.addEventListener('push', event => {
  let payload = {}

  try {
    payload = event.data ? event.data.json() : {}
  } catch (error) {
    payload = {}
  }

  const title = payload.title || 'NOU 小幫手'
  const options = {
    body: payload.body || '',
    icon: payload.icon || '/icons/icon-192.png',
    badge: '/icons/icon-192.png',
    // A tagged notification replaces the previous one with the same tag
    // instead of stacking, so a study timer's rounds don't pile up.
    tag: payload.tag || undefined,
    data: { url: (payload.data && payload.data.url) || '/' },
  }

  event.waitUntil(self.registration.showNotification(title, options))
})

self.addEventListener('notificationclick', event => {
  event.notification.close()

  const targetUrl =
    (event.notification.data && event.notification.data.url) || '/'

  event.waitUntil(
    clients
      .matchAll({ type: 'window', includeUncontrolled: true })
      .then(clientList => {
        for (const client of clientList) {
          if (client.url === targetUrl && 'focus' in client) {
            return client.focus()
          }
        }

        if (clients.openWindow) {
          return clients.openWindow(targetUrl)
        }
      })
  )
})
