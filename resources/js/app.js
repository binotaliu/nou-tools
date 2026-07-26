import './bootstrap'

// Registers the offline-support service worker (see public/sw.js). It caches
// previously-visited home and /schedules/{schedule} pages (plus their assets)
// and is registered site-wide since the worker's fetch handler scopes the
// offline behavior to those routes.
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {})
  })
}

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
// every DOM mutation (Alpine renders schedule rows client-side) so
// dynamically-inserted elements are covered too, not just what's in the
// initial HTML.
function updateOfflineLinkStates() {
  const offline = window.Alpine?.store('network')?.offline ?? false

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

// Global online/offline flag, shared via Alpine.store so the schedule page's
// offline banner and the (separately-scoped) schedule-items component can
// both react to the same state. Registered on 'alpine:init' — app.js runs
// before the deferred Alpine CDN bundle (see layout.blade.php), so this
// listener is always in place before Alpine fires that event.
document.addEventListener('alpine:init', () => {
  window.Alpine.store('network', {
    offline: typeof navigator !== 'undefined' && !navigator.onLine,

    init() {
      // navigator.onLine only reflects whether *a* network interface is up,
      // not whether our server is actually reachable — so on top of the
      // online/offline events, probe the app's own health-check route.
      // This is what makes offline detection correct even when the page is
      // opened fresh while already offline (onLine can lag or be wrong at
      // that point, especially on a page restored from the service worker
      // cache).
      this.checkConnectivity()

      window.addEventListener('online', () => this.checkConnectivity())
      window.addEventListener('offline', () => this.setOffline(true))
    },

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
  })
})

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

// Client-side time helpers, shared by the greeting and schedule-items
// components. This module runs before the deferred Alpine CDN bundle boots
// (see the @vite/Alpine ordering in components/layout.blade.php), which
// guarantees window.NouTime / window.nouGreeting exist by the time Alpine
// evaluates any x-data that references them.
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

// Alpine factory for the greeting card. Everything is derived from the
// viewer's local clock so an overseas student sees the greeting that
// matches their own time of day.
window.nouGreeting =
  window.nouGreeting ||
  function (config) {
    // Persisted so the viewer's chosen widget style (normal vs. compact)
    // survives reloads.
    const compactStorageKey = 'nou_greeting_compact_v1'

    return {
      greetingText: '',
      dateString: '',
      semesterInfo: '',
      compactMode: false,
      compactDateString: '',
      compactSemesterInfo: '',
      showTaiwanClock: false,
      taiwanHour: '',
      taiwanMinute: '',
      taiwanDateString: '',

      init() {
        this.compactMode = localStorage.getItem(compactStorageKey) === '1'

        this.refreshGreeting(config)
        // Re-derive the greeting/date/week each minute so a page left open
        // across a boundary (e.g. 11:59 -> 12:00, or midnight) doesn't stay
        // stuck on a stale "早安"/date/semester week.
        setInterval(() => this.refreshGreeting(config), 60 * 1000)

        const T = window.NouTime
        this.showTaiwanClock = T.differsFromTaipei(new Date())

        if (this.showTaiwanClock) {
          this.refreshTaiwanClock()
          // Only the minute digits are shown, so a per-second tick is enough
          // to keep the clock from drifting a minute behind.
          setInterval(() => this.refreshTaiwanClock(), 1000)
        }
      },

      refreshGreeting(config) {
        const T = window.NouTime
        const now = new Date()
        const hour = now.getHours()

        this.greetingText =
          hour >= 5 && hour < 12
            ? '早安'
            : hour >= 12 && hour < 18
              ? '午安'
              : '晚安'

        this.dateString =
          now.getFullYear() +
          ' 年 ' +
          (now.getMonth() + 1) +
          ' 月 ' +
          now.getDate() +
          ' 日 (' +
          T.WEEKDAYS[now.getDay()] +
          ')'

        this.compactDateString =
          now.getFullYear() +
          '/' +
          T.pad(now.getMonth() + 1) +
          '/' +
          T.pad(now.getDate()) +
          ' (' +
          T.WEEKDAYS[now.getDay()] +
          ')'

        this.semesterInfo = this.buildSemesterInfo(config, now)
        this.compactSemesterInfo = this.buildCompactSemesterInfo(config, now)
      },

      toggleCompact() {
        this.compactMode = !this.compactMode
        localStorage.setItem(compactStorageKey, this.compactMode ? '1' : '0')
      },

      refreshTaiwanClock() {
        const T = window.NouTime
        const now = new Date()

        const { hour, minute } = T.taipeiHM(now)
        this.taiwanHour = hour
        this.taiwanMinute = minute

        const ymd = T.taipeiYmd(now)
        const [y, m, d] = ymd.split('-').map(Number)
        this.taiwanDateString =
          y + '/' + m + '/' + d + ' (' + T.weekdayFromYmd(ymd) + ')'
      },

      buildSemesterInfo(config, now) {
        const T = window.NouTime

        if (!config.semesterStart || !config.semesterEnd) {
          return config.semesterCode || ''
        }

        // Zero-padded Y-m-d strings compare chronologically as text.
        const today = T.localYmd(now)

        if (today < config.semesterStart) {
          return config.semesterLabel + '尚未開始'
        }

        if (today > config.semesterEnd) {
          return config.semesterLabel + '已結束'
        }

        const start = Date.parse(config.semesterStart + 'T00:00:00Z')
        const current = Date.parse(today + 'T00:00:00Z')
        const weekNumber = Math.floor((current - start) / 86400000 / 7) + 1

        return config.semesterLabel + '第' + T.chineseNumber(weekNumber) + '週'
      },

      // Compact single-line rendering of the semester, e.g. "115 暑 W3".
      // Uses a ROC year + single-character term abbreviation and an arabic
      // week number, mirroring Str::toShortSemesterDisplay but terser still.
      buildCompactSemesterInfo(config, now) {
        const T = window.NouTime

        const match = /^(\d{4})([ABC])$/.exec(config.semesterCode || '')

        if (!match) {
          return config.semesterCode || ''
        }

        const rocYear = Number(match[1]) - 1911
        const termChar = { A: '上', B: '下', C: '暑' }[match[2]]
        const shortLabel = rocYear + ' ' + termChar

        if (!config.semesterStart || !config.semesterEnd) {
          return shortLabel
        }

        const today = T.localYmd(now)

        if (today < config.semesterStart || today > config.semesterEnd) {
          return shortLabel
        }

        const start = Date.parse(config.semesterStart + 'T00:00:00Z')
        const current = Date.parse(today + 'T00:00:00Z')
        const weekNumber = Math.floor((current - start) / 86400000 / 7) + 1

        return shortLabel + ' W' + weekNumber
      },
    }
  }

// Alpine factory for the school calendar card. School events are published
// on Taipei's academic calendar, not the viewer's own, so "today" and every
// day count here are anchored to Asia/Taipei rather than the local clock
// (contrast with nouGreeting/nouScheduleItems above). Overseas students get
// a small hint instead, telling them the dates are in Taiwan time.
window.nouSchoolCalendar =
  window.nouSchoolCalendar ||
  function (events) {
    const T = window.NouTime

    return {
      events,
      today: T.taipeiYmd(new Date()),
      showTaipeiHint: T.differsFromTaipei(new Date()),

      init() {
        // Daily-granularity data, so an hourly refresh (plus on tab-return) is
        // enough to keep "today" and the local-vs-Taipei hint from going stale
        // in a long-lived or offline-restored tab.
        setInterval(() => this.refreshNow(), 60 * 60 * 1000)
        document.addEventListener('visibilitychange', () => {
          if (!document.hidden) {
            this.refreshNow()
          }
        })
      },

      refreshNow() {
        const now = new Date()
        this.today = T.taipeiYmd(now)
        this.showTaipeiHint = T.differsFromTaipei(now)
      },

      statusOf(event) {
        return this.today >= event.start && this.today <= event.end
          ? 'ongoing'
          : 'upcoming'
      },

      daysUntilOf(event) {
        return this.today >= event.start
          ? 0
          : T.diffInDaysYmd(this.today, event.start)
      },

      // Events that have not fully ended yet, decorated with the status/count
      // that used to be computed server-side, sorted by start date.
      get activeEvents() {
        return this.events
          .filter(event => event.end >= this.today)
          .map(event => ({
            ...event,
            status: this.statusOf(event),
            daysUntil: this.daysUntilOf(event),
          }))
          .sort((a, b) => (a.start < b.start ? -1 : a.start > b.start ? 1 : 0))
      },

      // Nearest countdown-flagged event that is still upcoming or ongoing.
      get countdownEvent() {
        return this.activeEvents.find(event => event.countdown) ?? null
      },

      // Remaining active events, excluding whichever one is shown as the
      // countdown card (kept in the list, hidden on screen, for print).
      isCountdownMatch(event) {
        const countdown = this.countdownEvent

        return (
          !!countdown &&
          event.name === countdown.name &&
          event.start === countdown.start
        )
      },

      monthDayZh(ymd) {
        const [, m, d] = ymd.split('-').map(Number)

        return m + ' 月 ' + d + ' 日'
      },

      yearMonthDayZh(ymd) {
        const [y, m, d] = ymd.split('-').map(Number)

        return y + ' 年 ' + m + ' 月 ' + d + ' 日'
      },

      // "Y 年 n 月 j 日 – n 月 j 日" (end omitted for single-day events).
      dateRange(event) {
        let range = this.yearMonthDayZh(event.start)

        if (event.start !== event.end) {
          range += ' – ' + this.monthDayZh(event.end)
        }

        return range
      },

      // "n 月 j 日 – n 月 j 日", used in the list rows (no year).
      shortDateRange(event) {
        let range = this.monthDayZh(event.start)

        if (event.start !== event.end) {
          range += ' – ' + this.monthDayZh(event.end)
        }

        return range
      },
    }
  }
