export default function nouToolsScheduleItems(items) {
  const T = window.NouTime

  return {
    items: items,
    now: Date.now(),

    init() {
      // Keep "next class" and the sort fresh as time passes, and
      // recompute when a backgrounded/offline-restored tab returns.
      setInterval(() => {
        this.now = Date.now()
      }, 60000)
      document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
          this.now = Date.now()
        }
      })
    },

    // Earliest class that has not yet ended, or null when none remain.
    nextOf(item) {
      let best = null
      let bestStart = Infinity

      for (const schedule of item.schedules) {
        if (Date.parse(schedule.instantEnd) < this.now) {
          continue
        }

        const start = Date.parse(schedule.instantStart)

        if (start < bestStart) {
          bestStart = start
          best = schedule
        }
      }

      return best
    },

    // Items sorted by their next class; those without an upcoming class
    // fall to the bottom. `i` keeps the sort stable and keys the x-for.
    get rows() {
      return this.items
        .map((item, i) => ({
          item,
          next: this.nextOf(item),
          i,
        }))
        .sort((a, b) => {
          const aStart = a.next ? Date.parse(a.next.instantStart) : Infinity
          const bStart = b.next ? Date.parse(b.next.instantStart) : Infinity

          return aStart === bStart ? a.i - b.i : aStart - bStart
        })
    },

    // Split a teacher name so a trailing "老師" can render smaller,
    // mirroring the previous server-side markup.
    teacher(item) {
      const name = item.teacherName

      if (!name) {
        return null
      }

      return name.endsWith('老師')
        ? { base: name.slice(0, -2), suffix: '老師' }
        : { base: name, suffix: '' }
    },

    // Official Taipei date/time of the next class.
    taipeiDate(next) {
      return T.monthDay(next.ymd) + ' (' + T.weekdayFromYmd(next.ymd) + ')'
    },

    taipeiTime(next) {
      return next.startTime ? next.startTime + ' ~ ' + next.endTime : null
    },

    // Secondary "your time" line — only when the viewer's zone differs
    // from Taipei. Includes the local date when it lands on a different
    // day than the Taipei date.
    localHint(next) {
      if (!next.startTime) {
        return null
      }

      const start = new Date(next.instantStart)
      const end = new Date(next.instantEnd)

      if (!T.differsFromTaipei(start)) {
        return null
      }

      let datePrefix = ''
      const localStartYmd = T.localYmd(start)

      if (localStartYmd !== next.ymd) {
        datePrefix =
          T.monthDay(localStartYmd) +
          ' (' +
          T.weekdayFromYmd(localStartYmd) +
          ') '
      }

      return (
        '你的時間 · ' +
        datePrefix +
        T.localHM(start) +
        ' ~ ' +
        T.localHM(end) +
        ' (' +
        T.gmtLabel(start) +
        ')'
      )
    },
  }
}
