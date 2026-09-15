import { onMounted, onUnmounted, reactive, toRefs } from 'vue'

// Vue port of the `nouToolsGreeting` Alpine.data() component (defined
// inline in resources/js/app.js, registered globally via
// `window.Alpine.data('nouToolsGreeting', nouToolsGreeting)`). Relies on
// `window.NouTime`, the same timezone-aware date helpers app.js defines
// unconditionally (independent of Alpine/Inertia), which is guaranteed to
// exist by the time this composable's `init()` runs since app.js always
// executes before Vue mounts.
//
// `config` mirrors the Alpine version's `{ semesterLabel, semesterCode,
// semesterStart, semesterEnd }`.
export default function useGreeting(config) {
  const compactStorageKey = 'nou_greeting_compact_v1'

  const state = reactive({
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
  })

  let greetingInterval = null
  let clockInterval = null

  function buildSemesterInfo(now) {
    const T = window.NouTime

    if (!config.semesterStart || !config.semesterEnd) {
      return config.semesterCode || ''
    }

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
  }

  function buildCompactSemesterInfo(now) {
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
  }

  function refreshGreeting() {
    const T = window.NouTime
    const now = new Date()
    const hour = now.getHours()

    state.greetingText =
      hour >= 5 && hour < 12
        ? '早安'
        : hour >= 12 && hour < 18
          ? '午安'
          : '晚安'

    state.dateString =
      now.getFullYear() +
      ' 年 ' +
      (now.getMonth() + 1) +
      ' 月 ' +
      now.getDate() +
      ' 日 (' +
      T.WEEKDAYS[now.getDay()] +
      ')'

    state.compactDateString =
      now.getFullYear() +
      '/' +
      T.pad(now.getMonth() + 1) +
      '/' +
      T.pad(now.getDate()) +
      ' (' +
      T.WEEKDAYS[now.getDay()] +
      ')'

    state.semesterInfo = buildSemesterInfo(now)
    state.compactSemesterInfo = buildCompactSemesterInfo(now)
  }

  function refreshTaiwanClock() {
    const T = window.NouTime
    const now = new Date()

    const { hour, minute } = T.taipeiHM(now)
    state.taiwanHour = hour
    state.taiwanMinute = minute

    const ymd = T.taipeiYmd(now)
    const [y, m, d] = ymd.split('-').map(Number)
    state.taiwanDateString =
      y + '/' + m + '/' + d + ' (' + T.weekdayFromYmd(ymd) + ')'
  }

  function toggleCompact() {
    state.compactMode = !state.compactMode

    try {
      localStorage.setItem(compactStorageKey, state.compactMode ? '1' : '0')
    } catch (error) {
      // ignore storage failures (private mode, etc.)
    }
  }

  onMounted(() => {
    try {
      state.compactMode = localStorage.getItem(compactStorageKey) === '1'
    } catch (error) {
      state.compactMode = false
    }

    refreshGreeting()
    // Re-derive the greeting/date/week each minute so a page left open
    // across a boundary (e.g. 11:59 -> 12:00, or midnight) doesn't stay
    // stuck on a stale value.
    greetingInterval = setInterval(refreshGreeting, 60 * 1000)

    const T = window.NouTime
    state.showTaiwanClock = T.differsFromTaipei(new Date())

    if (state.showTaiwanClock) {
      refreshTaiwanClock()
      clockInterval = setInterval(refreshTaiwanClock, 1000)
    }
  })

  onUnmounted(() => {
    if (greetingInterval) {
      clearInterval(greetingInterval)
    }

    if (clockInterval) {
      clearInterval(clockInterval)
    }
  })

  return {
    ...toRefs(state),
    toggleCompact,
  }
}
