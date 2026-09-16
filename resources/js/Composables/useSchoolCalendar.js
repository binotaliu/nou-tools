import { computed, onMounted, onUnmounted, ref } from 'vue'

// School events are published on Taipei's academic calendar, not the
// viewer's own, so "today" and every day count here are anchored to
// Asia/Taipei rather than the local clock. `events` is the raw
// upcoming/ongoing events array; `showPastEvents` is true when browsing a
// non-current semester's full calendar.
export default function useSchoolCalendar(events, showPastEvents = false) {
  const T = window.NouTime

  const today = ref(T.taipeiYmd(new Date()))
  const showTaipeiHint = ref(T.differsFromTaipei(new Date()))

  function refreshNow() {
    const now = new Date()
    today.value = T.taipeiYmd(now)
    showTaipeiHint.value = T.differsFromTaipei(now)
  }

  let interval = null

  function onVisibilityChange() {
    if (!document.hidden) {
      refreshNow()
    }
  }

  onMounted(() => {
    // Daily-granularity data, so an hourly refresh (plus on tab-return) is
    // enough to keep "today" and the local-vs-Taipei hint from going stale
    // in a long-lived or offline-restored tab.
    interval = setInterval(refreshNow, 60 * 60 * 1000)
    document.addEventListener('visibilitychange', onVisibilityChange)
  })

  onUnmounted(() => {
    if (interval) {
      clearInterval(interval)
    }
    document.removeEventListener('visibilitychange', onVisibilityChange)
  })

  function statusOf(event) {
    return today.value >= event.start && today.value <= event.end
      ? 'ongoing'
      : 'upcoming'
  }

  function daysUntilOf(event) {
    return today.value >= event.start
      ? 0
      : T.diffInDaysYmd(today.value, event.start)
  }

  // Events to display: for the current semester, only ones that have not
  // fully ended yet; for a specific non-current semester (showPastEvents),
  // the semester's whole calendar. Decorated with status/count, sorted by
  // start date.
  const activeEvents = computed(() =>
    events
      .filter(event => showPastEvents || event.end >= today.value)
      .map(event => ({
        ...event,
        status: statusOf(event),
        daysUntil: daysUntilOf(event),
      }))
      .sort((a, b) => (a.start < b.start ? -1 : a.start > b.start ? 1 : 0))
  )

  // Nearest countdown-flagged event that is still upcoming or ongoing.
  // Never shown when browsing a past semester's full calendar.
  const countdownEvent = computed(() => {
    if (showPastEvents) {
      return null
    }

    return activeEvents.value.find(event => event.countdown) ?? null
  })

  // Remaining active events, excluding whichever one is shown as the
  // countdown card (kept in the list, hidden on screen, for print).
  function isCountdownMatch(event) {
    const countdown = countdownEvent.value

    return (
      !!countdown &&
      event.name === countdown.name &&
      event.start === countdown.start
    )
  }

  function monthDayZh(ymd) {
    const [, m, d] = ymd.split('-').map(Number)

    return `${m} 月 ${d} 日`
  }

  function yearMonthDayZh(ymd) {
    const [y, m, d] = ymd.split('-').map(Number)

    return `${y} 年 ${m} 月 ${d} 日`
  }

  // "Y 年 n 月 j 日 – n 月 j 日" (end omitted for single-day events).
  function dateRange(event) {
    let range = yearMonthDayZh(event.start)

    if (event.start !== event.end) {
      range += ' – ' + monthDayZh(event.end)
    }

    return range
  }

  // "n 月 j 日 – n 月 j 日", used in the list rows (no year).
  function shortDateRange(event) {
    let range = monthDayZh(event.start)

    if (event.start !== event.end) {
      range += ' – ' + monthDayZh(event.end)
    }

    return range
  }

  return {
    showTaipeiHint,
    activeEvents,
    countdownEvent,
    isCountdownMatch,
    dateRange,
    shortDateRange,
  }
}
