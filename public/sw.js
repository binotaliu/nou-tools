// Minimal offline support: keeps a previously-visited home/schedule/directory
// page (and the assets it needs) available when the network is down, and
// shows a generic offline page for any other route that isn't cached.
const CACHE_VERSION = 'v4'
const PAGE_CACHE = `nou-schedule-pages-${CACHE_VERSION}`
const RUNTIME_CACHE = `nou-runtime-${CACHE_VERSION}`

// Matches /schedules/{token} only — not /schedules/create or nested routes
// like /schedules/{token}/edit, which aren't meant to work offline.
const SCHEDULE_SHOW_PATTERN = /^\/schedules\/([^/]+)$/

// Generic fallback shown for any other page when it isn't cached and the
// network is unreachable. Precached below so it's always available, even if
// the visitor never opened it directly.
const OFFLINE_URL = '/offline'

function isHomeUrl(url) {
  return url.origin === self.location.origin && url.pathname === '/'
}

function isDirectoryUrl(url) {
  return url.origin === self.location.origin && url.pathname === '/directory'
}

function isScheduleShowUrl(url) {
  if (url.origin !== self.location.origin) {
    return false
  }

  const match = SCHEDULE_SHOW_PATTERN.exec(url.pathname)

  return !!match && match[1] !== 'create'
}

// Third-party origins whose assets are safe to cache for offline rendering
// (currently just Twemoji, loaded on demand by the study room — see
// resources/js/Pages/StudyRoom/Show.vue).
function isCacheableCrossOrigin(url) {
  return url.hostname === 'cdn.jsdelivr.net'
}

// Standard gateway errors plus Cloudflare's origin-error range (520-527):
// these mean the origin is unreachable, not that the app itself returned a
// meaningful response. A real 401/403/404/429/etc from the app — including
// a Cloudflare bot-challenge page, which also answers 403 — must NOT match
// here, or we'd hide it behind the offline page and trap the visitor.
function isOutageStatus(status) {
  return (
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
            .filter(key => key !== PAGE_CACHE && key !== RUNTIME_CACHE)
            .map(key => caches.delete(key))
        )
      )
      .then(() => self.clients.claim())
  )
})

async function networkFirst(request) {
  const cache = await caches.open(PAGE_CACHE)

  try {
    const response = await fetch(request)

    if (response && response.ok) {
      cache.put(request, response.clone())
      return response
    }

    // A gateway/outage response (e.g. Cloudflare's 5xx page when the origin
    // is down) isn't a fetch failure, so it wouldn't hit the catch block
    // below. Prefer the last good cached page over showing that error page.
    // Any other status (404, 403, a Cloudflare challenge, etc.) is a real
    // response and must be passed through as-is.
    if (response && isOutageStatus(response.status)) {
      return (await cache.match(request)) || response
    }

    return response
  } catch (error) {
    const cached = await cache.match(request)

    if (cached) {
      return cached
    }

    throw error
  }
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
    return
  }

  // Navigations: home and schedule show pages are meant to work offline.
  if (request.mode === 'navigate') {
    if (isHomeUrl(url) || isScheduleShowUrl(url) || isDirectoryUrl(url)) {
      event.respondWith(networkFirst(request))
      return
    }

    // Every other page (announcements, create/edit flows, etc.) isn't
    // cached — pass it straight to the network like there were no service
    // worker at all, except when that fetch fails (offline) or comes back
    // as a gateway/outage response (e.g. Cloudflare's 5xx page when the
    // origin is down), in which case show the generic offline fallback
    // instead. A real app response — including a 401/403/404 or a
    // Cloudflare bot-challenge page — is passed through untouched.
    if (url.origin === self.location.origin && url.pathname !== OFFLINE_URL) {
      event.respondWith(
        fetch(request)
          .then(async response => {
            if (!response || !isOutageStatus(response.status)) {
              return response
            }

            const cache = await caches.open(PAGE_CACHE)

            return (await cache.match(OFFLINE_URL)) || response
          })
          .catch(async () => {
            const cache = await caches.open(PAGE_CACHE)

            return (await cache.match(OFFLINE_URL)) || Response.error()
          })
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
