// Minimal offline support: keeps a previously-visited /schedules/{schedule}
// page (and the assets it needs) available when the network is down.
const CACHE_VERSION = 'v2'
const PAGE_CACHE = `nou-schedule-pages-${CACHE_VERSION}`
const RUNTIME_CACHE = `nou-runtime-${CACHE_VERSION}`

// Matches /schedules/{token} only — not /schedules/create or nested routes
// like /schedules/{token}/edit, which aren't meant to work offline.
const SCHEDULE_SHOW_PATTERN = /^\/schedules\/([^/]+)$/

function isHomeUrl(url) {
  return url.origin === self.location.origin && url.pathname === '/'
}

function isScheduleShowUrl(url) {
  if (url.origin !== self.location.origin) {
    return false
  }

  const match = SCHEDULE_SHOW_PATTERN.exec(url.pathname)

  return !!match && match[1] !== 'create'
}

// Third-party origins whose assets are safe to cache for offline rendering
// (currently just the Alpine.js CDN loaded from the layout).
function isCacheableCrossOrigin(url) {
  return url.hostname === 'cdn.jsdelivr.net'
}

self.addEventListener('install', () => {
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

  // Navigations: home and schedule show pages are meant to work offline.
  // Every other page (announcements, create/edit flows, etc.) is left
  // untouched — pass straight through to the network like there were no
  // service worker at all, rather than routing it through the Cache API.
  if (request.mode === 'navigate') {
    if (isHomeUrl(url) || isScheduleShowUrl(url)) {
      event.respondWith(networkFirst(request))
    }

    return
  }

  if (url.origin === self.location.origin || isCacheableCrossOrigin(url)) {
    event.respondWith(staleWhileRevalidate(request))
  }
})
