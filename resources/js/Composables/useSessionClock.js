import { onMounted, onUnmounted, ref } from 'vue'

// Class times are published in Taipei time (UTC+8, no DST).
export function taipeiIso(ymd, time) {
  return `${ymd}T${time || '00:00'}:00+08:00`
}

const SOON_MS = 30 * 60 * 1000
const LINGER_MS = 30 * 60 * 1000

/**
 * A class's state at `nowMs`: 'soon' (starts within 30 minutes), 'live',
 * 'finished' (ended less than 30 minutes ago, still shown), 'ended' (hidden
 * by default) or 'upcoming'. Returns null when the class has no time.
 */
export function sessionState(ymd, startTime, endTime, nowMs) {
  if (!startTime) {
    return null
  }

  const start = new Date(taipeiIso(ymd, startTime)).getTime()
  const end = new Date(taipeiIso(ymd, endTime || startTime)).getTime()

  if (nowMs >= end + LINGER_MS) {
    return 'ended'
  }

  if (nowMs >= end) {
    return 'finished'
  }

  if (nowMs >= start) {
    return 'live'
  }

  return nowMs >= start - SOON_MS ? 'soon' : 'upcoming'
}

/** A reactive `now` (ms) that refreshes every 30 seconds while mounted. */
export default function useSessionClock() {
  const now = ref(Date.now())
  let timer = null

  onMounted(() => {
    now.value = Date.now()
    timer = setInterval(() => {
      now.value = Date.now()
    }, 30_000)
  })

  onUnmounted(() => clearInterval(timer))

  return { now }
}
