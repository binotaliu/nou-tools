import { taipeiIso } from './useSessionClock'

/**
 * The "your time" line for a class published in Taipei time: null when the
 * viewer's zone is UTC+8 (or the class has no time), otherwise
 * `你的時間 · 09/24 (四) 07:00 ~ 08:50 (GMT+6)` with the local date included
 * only when it differs from the Taipei date.
 */
export function localTimeHint(ymd, startTime, endTime) {
  if (!startTime) {
    return null
  }

  const T = window.NouTime
  const start = new Date(taipeiIso(ymd, startTime))
  const end = new Date(taipeiIso(ymd, endTime || startTime))

  if (!T.differsFromTaipei(start)) {
    return null
  }

  let datePrefix = ''
  const localStartYmd = T.localYmd(start)

  if (localStartYmd !== ymd) {
    datePrefix = `${T.monthDay(localStartYmd)} (${T.weekdayFromYmd(localStartYmd)}) `
  }

  return `你的時間 · ${datePrefix}${T.localHM(start)} ~ ${T.localHM(end)} (${T.gmtLabel(start)})`
}
