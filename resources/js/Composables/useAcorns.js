import { reactive } from 'vue'
import axios from 'axios'

// The viewer's acorn balance and recent ledger, read from GET /acorns. The
// server is the only authority (balance is derived from unexpired credits),
// so this only ever replaces its copy with a fresh response. `refresh()` is
// debounced because a study session can be recorded several times in a row.
export default function useAcorns() {
  let timer = null

  const acorns = reactive({
    loaded: false,
    open: false,
    balance: 0,
    studyToday: 0,
    studyDailyCap: 0,
    studyThisWeek: 0,
    studyWeeklyCap: 0,
    learningToday: 0,
    learningDailyCap: 0,
    nextExpiryAmount: null,
    nextExpiresAt: null,
    recent: [],

    async load() {
      try {
        const response = await axios.get('/acorns')
        Object.assign(acorns, response.data.acorns, { loaded: true })
      } catch {
        // The nameplate simply omits the balance until the next attempt.
      }
    },

    refresh() {
      clearTimeout(timer)
      timer = setTimeout(() => acorns.load(), 800)
    },

    expiryLabel() {
      if (!acorns.nextExpiresAt || !acorns.nextExpiryAmount) {
        return ''
      }

      // Credits expire at the end of a Taipei day, so the label names the
      // Taipei date they were last good on.
      const lastDay = new Date(new Date(acorns.nextExpiresAt).getTime() - 1)
      const label = new Intl.DateTimeFormat('zh-TW', {
        month: 'numeric',
        day: 'numeric',
        timeZone: 'Asia/Taipei',
      }).format(lastDay)

      return `${acorns.nextExpiryAmount} 顆將於 ${label} 後到期`
    },
  })

  return acorns
}
