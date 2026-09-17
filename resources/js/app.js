import './bootstrap'
import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'

// The CSP (style-src with no 'unsafe-inline') requires a nonce on every
// inline <style>, including the one Inertia's progress bar injects itself.
// Must be passed here rather than set on @inertiajs/core's config beforehand:
// createInertiaApp() calls config.replace() internally, which wipes out
// anything set before this call.
const cspNonce = document
  .querySelector('meta[name="csp-nonce"]')
  ?.getAttribute('content')

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: false })
    return pages[`./Pages/${name}.vue`]()
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
  nonce: cspNonce,
})

// Inertia does client-side navigation between pages, so the page_view GA
// event that a normal full page load fires never happens again after the
// first visit. The 'navigate' event fires for that first visit too (Inertia
// treats it as a navigation), so this one hook covers every page view,
// initial or SPA. `page.props.analyticsPage` is shared by
// HandleInertiaRequests and mirrors the masked route path from
// `data-analytics-page` on the (only ever server-rendered once) <body> tag.
// `page.props.analyticsTitle` overrides page_title for schedule pages, whose
// <Head title> is personalized with the student's own schedule/course names
// and would otherwise leak into analytics; `document.title` is the fallback
// for every other route.
router.on('navigate', event => trackPageView(event.detail.page))

function trackPageView(page) {
  if (typeof window.gtag !== 'function') {
    return
  }

  const path = page?.props?.analyticsPage

  if (!path) {
    return
  }

  window.gtag('event', 'page_view', {
    page_path: path,
    page_title: page?.props?.analyticsTitle || document.title,
    page_location: window.location.href,
  })
}

// Registers the offline-support service worker (see public/sw.js). It caches
// previously-visited home and /schedules/{schedule} pages (plus their assets)
// and is registered site-wide since the worker's fetch handler scopes the
// offline behavior to those routes.
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {})
  })
}

// Chrome/Edge/Android fire this ahead of time and expect preventDefault() so
// the browser's own mini-infobar is suppressed in favor of our own install
// banner (see usePwaInstallBanner.js), which re-triggers the captured
// event's prompt() on click.
window.__nouInstallPrompt = null
window.addEventListener('beforeinstallprompt', event => {
  event.preventDefault()
  window.__nouInstallPrompt = event
  window.dispatchEvent(new CustomEvent('nou:install-prompt-ready'))
})
window.addEventListener('appinstalled', () => {
  window.__nouInstallPrompt = null
})

// A link is left alone while offline if it goes to the homepage, to a
// schedule's own show page (the only pages that can actually work without a
// connection), or if it opts out explicitly via data-offline-allow (used for
// the video-call link on the schedule page, which doesn't need our server).
function isOfflineAllowedLink(link) {
  if (link.hasAttribute('data-offline-allow')) {
    return true
  }

  let url

  try {
    url = new URL(link.href, window.location.href)
  } catch (error) {
    return true
  }

  if (url.origin !== window.location.origin) {
    return false
  }

  if (url.pathname === '/') {
    return true
  }

  const match = /^\/schedules\/([^/]+)$/.exec(url.pathname)

  return !!match && match[1] !== 'create'
}

// Visually disables and blocks navigation on every other link while offline,
// plus any other control opting in via data-offline-disable (e.g. the term
// switcher — switching semesters means a fresh, uncached page load). Runs on
// every DOM mutation (Inertia/Vue re-render page content client-side) so
// dynamically-inserted elements are covered too, not just what's in the
// initial HTML.
function updateOfflineLinkStates() {
  const offline = window.NouNetwork?.offline ?? false

  document.querySelectorAll('a[href]').forEach(link => {
    const disabled = offline && !isOfflineAllowedLink(link)

    link.classList.toggle('pointer-events-none', disabled)
    link.classList.toggle('opacity-50', disabled)

    if (disabled) {
      link.setAttribute('aria-disabled', 'true')
    } else {
      link.removeAttribute('aria-disabled')
    }
  })

  document.querySelectorAll('[data-offline-disable]').forEach(el => {
    el.disabled = offline
    el.classList.toggle('opacity-50', offline)
    el.classList.toggle('cursor-not-allowed', offline)
  })
}

new MutationObserver(() => updateOfflineLinkStates()).observe(
  document.documentElement,
  {
    childList: true,
    subtree: true,
  }
)

// Global online/offline flag, read by updateOfflineLinkStates() above and by
// the schedule/directory pages' own offline banners. navigator.onLine only
// reflects whether *a* network interface is up, not whether our server is
// actually reachable — so on top of the online/offline events, this probes
// the app's own health-check route. That's what makes offline detection
// correct even when the page is opened fresh while already offline
// (onLine can lag or be wrong at that point, especially on a page restored
// from the service worker cache).
window.NouNetwork = {
  offline: typeof navigator !== 'undefined' && !navigator.onLine,

  setOffline(value) {
    this.offline = value
    updateOfflineLinkStates()
  },

  async checkConnectivity() {
    try {
      const response = await fetch('/up', { cache: 'no-store' })
      this.setOffline(!response.ok)
    } catch (error) {
      this.setOffline(true)
    }
  },
}

window.NouNetwork.checkConnectivity()
window.addEventListener('online', () => window.NouNetwork.checkConnectivity())
window.addEventListener('offline', () => window.NouNetwork.setOffline(true))

const trackAnalyticsEvent = (eventName, params = {}) => {
  if (typeof window.gtag !== 'function' || !eventName) {
    return
  }

  window.gtag('event', eventName, params)
}

document.addEventListener('click', event => {
  const target = event.target.closest('[data-analytics-event]')

  if (!target) {
    return
  }

  const { analyticsEvent, analyticsFeature, analyticsLabel } = target.dataset

  trackAnalyticsEvent(analyticsEvent, {
    feature: analyticsFeature,
    label: analyticsLabel,
  })
})

// Client-side time helpers, used by the useGreeting/useSchoolCalendar and
// useMarkdownContainers Vue composables (the latter keeps `:::countdown`
// article containers from going stale — see resources/js/Composables).
//
// Everything here is timezone-aware on purpose: National Open University has
// overseas students, so greetings and "next class" must reflect the viewer's
// own local time, not the server's clock.
window.NouTime =
  window.NouTime ||
  (function () {
    // Localised short weekday names, indexed by Date#getDay() (0 = Sunday).
    const WEEKDAYS = ['日', '一', '二', '三', '四', '五', '六']

    // National Open University classes are published in Taipei time (UTC+8,
    // no DST). When the viewer sits at the same offset the "your time" hint
    // is redundant, so components suppress it.
    const TAIPEI_OFFSET_MINUTES = 480

    function pad(n) {
      return String(n).padStart(2, '0')
    }

    // Minutes east of UTC for the viewer's zone at the given instant
    // (e.g. UTC+8 => 480). getTimezoneOffset() is minutes *behind* UTC.
    function localOffsetMinutes(date) {
      return -date.getTimezoneOffset()
    }

    // The viewer's local calendar date (Y-m-d) for the given instant.
    function localYmd(date) {
      return (
        date.getFullYear() +
        '-' +
        pad(date.getMonth() + 1) +
        '-' +
        pad(date.getDate())
      )
    }

    // The viewer's local wall-clock time (HH:MM) for the given instant.
    function localHM(date) {
      return pad(date.getHours()) + ':' + pad(date.getMinutes())
    }

    // Weekday char for a Taipei Y-m-d string. Read via UTC so the result is
    // independent of the viewer's zone.
    function weekdayFromYmd(ymd) {
      const [y, m, d] = ymd.split('-').map(Number)
      return WEEKDAYS[new Date(Date.UTC(y, m - 1, d)).getUTCDay()]
    }

    // "M/D" (no leading zeros) for a Y-m-d string.
    function monthDay(ymd) {
      const [, m, d] = ymd.split('-').map(Number)
      return m + '/' + d
    }

    // "GMT+8" / "GMT-5" / "GMT+5:30" label for the viewer's zone.
    function gmtLabel(date) {
      const offset = localOffsetMinutes(date)
      const sign = offset >= 0 ? '+' : '-'
      const hours = Math.trunc(Math.abs(offset) / 60)
      const minutes = Math.abs(offset) % 60
      return 'GMT' + sign + hours + (minutes ? ':' + pad(minutes) : '')
    }

    // Does the viewer's zone differ from Taipei at this instant?
    function differsFromTaipei(date) {
      return localOffsetMinutes(date) !== TAIPEI_OFFSET_MINUTES
    }

    // { hour: 'HH', minute: 'mm' } in Asia/Taipei wall-clock time (24h,
    // zero-padded), read via Intl so it stays correct regardless of the
    // viewer's own zone or DST rules.
    function taipeiHM(date) {
      const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Taipei',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
      }).formatToParts(date)

      const lookup = Object.fromEntries(parts.map(p => [p.type, p.value]))

      return { hour: lookup.hour, minute: lookup.minute }
    }

    // The Taipei calendar date (Y-m-d) for the given instant, regardless of
    // the viewer's own zone. Used for school-calendar events, which are
    // published on Taipei's academic calendar rather than the viewer's.
    function taipeiYmd(date) {
      const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Taipei',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
      }).formatToParts(date)

      const lookup = Object.fromEntries(parts.map(p => [p.type, p.value]))

      return lookup.year + '-' + lookup.month + '-' + lookup.day
    }

    // Whole calendar days between two Y-m-d strings (to minus from), read as
    // UTC midnights so the result is independent of the viewer's zone.
    function diffInDaysYmd(fromYmd, toYmd) {
      const [fy, fm, fd] = fromYmd.split('-').map(Number)
      const [ty, tm, td] = toYmd.split('-').map(Number)
      const from = Date.UTC(fy, fm - 1, fd)
      const to = Date.UTC(ty, tm - 1, td)

      return Math.round((to - from) / 86400000)
    }

    // Mirror of the PHP Str::toChineseNumber macro (1..99) so the semester
    // week reads identically to the previous server-rendered output.
    function chineseNumber(n) {
      if (n > 99) {
        return ' ' + n + ' '
      }

      const digits = [
        '零',
        '一',
        '二',
        '三',
        '四',
        '五',
        '六',
        '七',
        '八',
        '九',
      ]

      if (n <= 10) {
        return n === 10 ? '十' : digits[n]
      }

      if (n < 20) {
        return '十' + (n % 10 ? digits[n % 10] : '')
      }

      const tens = Math.trunc(n / 10)
      const ones = n % 10

      return (
        (tens === 1 ? '十' : digits[tens] + '十') + (ones ? digits[ones] : '')
      )
    }

    return {
      WEEKDAYS,
      pad,
      localOffsetMinutes,
      localYmd,
      localHM,
      weekdayFromYmd,
      monthDay,
      gmtLabel,
      differsFromTaipei,
      taipeiYmd,
      taipeiHM,
      diffInDaysYmd,
      chineseNumber,
    }
  })()
