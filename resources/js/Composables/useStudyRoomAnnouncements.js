import { onUnmounted, watch } from 'vue'

const ROOM_BATCH_MS = 3000

function durationMinutes(seat) {
  if (!seat.timerEndsAt || !seat.timerStartedAt) {
    return null
  }

  return Math.round(
    (Date.parse(seat.timerEndsAt) - Date.parse(seat.timerStartedAt)) / 60000
  )
}

// What the study room says without being asked (the page's seat focus
// handler speaks 已入座/已離開座位 itself):
//
// - your own timer changing: started, paused, resumed, break, next round,
//   stopped, activity changed. These are read off your seat's columns
//   rather than hooked into the buttons, so a change made in another tab or
//   by the server reads the same.
// - your countdown reaching zero (focus done, break over).
// - opt-in (voice settings): the time left every N minutes, and neighbours
//   or floor-mates sitting down or getting up, collected for a few seconds
//   into one sentence so a busy moment doesn't flood the reader.
export default function useStudyRoomAnnouncements({
  socket,
  timer,
  announcer,
  settings,
}) {
  let lastEndAnnounced = null
  let lastIntervalMark = null
  let roomMessages = []
  let roomFlushHandle = null

  function snapshot() {
    const seat = socket.mySeat()

    if (!seat) {
      return null
    }

    return {
      code: seat.code,
      timerMode: seat.timerMode,
      timerPhase: seat.timerPhase,
      timerRound: seat.timerRound,
      pausedAt: seat.pausedAt,
      activity: seat.activity,
      timerEndsAt: seat.timerEndsAt,
      timerStartedAt: seat.timerStartedAt,
    }
  }

  function describeChange(before, after) {
    if (!before.timerMode && after.timerMode) {
      const minutes = durationMinutes(after)

      return (
        '開始專注：' +
        (after.activity || '專注') +
        (minutes ? '，' + minutes + ' 分鐘' : '，正數計時')
      )
    }

    if (before.timerMode && !after.timerMode) {
      return '已結束計時'
    }

    if (!before.pausedAt && after.pausedAt) {
      return '已暫停'
    }

    if (before.pausedAt && !after.pausedAt) {
      return '已繼續'
    }

    if (before.timerPhase === 'focus' && after.timerPhase === 'break') {
      const minutes = durationMinutes(after)

      return '開始休息' + (minutes ? ' ' + minutes + ' 分鐘' : '')
    }

    if (before.timerPhase === 'break' && after.timerPhase === 'focus') {
      return '開始第 ' + after.timerRound + ' 輪專注'
    }

    if (
      after.timerPhase === 'focus' &&
      before.activity &&
      after.activity &&
      before.activity !== after.activity
    ) {
      return '已變更活動：' + after.activity
    }

    return null
  }

  // A countdown that had already ended when we first saw it (a reload
  // mid-overtime) is not news.
  function markAlreadyEnded(current) {
    if (
      current &&
      current.timerEndsAt &&
      Date.parse(current.timerEndsAt) <= Date.now()
    ) {
      lastEndAnnounced = current.timerEndsAt
    }
  }

  watch(
    snapshot,
    (after, before) => {
      lastIntervalMark = null
      markAlreadyEnded(after)

      // Taking or leaving the seat is announced by the seat focus handler.
      if (!before || !after || before.code !== after.code) {
        return
      }

      const message = describeChange(before, after)

      if (message) {
        announcer.say(message)
      }
    },
    { deep: true }
  )

  function checkTimerEnd(seat) {
    if (
      !seat.timerEndsAt ||
      seat.pausedAt ||
      Date.parse(seat.timerEndsAt) > timer.now ||
      lastEndAnnounced === seat.timerEndsAt
    ) {
      return
    }

    lastEndAnnounced = seat.timerEndsAt

    if (seat.timerPhase === 'break') {
      announcer.say('休息結束，可以開始下一輪')
    } else if (seat.timerMode === 'pomodoro') {
      announcer.say('專注時間到，可以休息了')
    } else {
      announcer.say('倒數結束')
    }
  }

  // Counts whole minutes left (or elapsed, for 正數) and speaks when that
  // count lands on a multiple of the chosen interval, once per mark.
  function checkInterval(seat) {
    const every = settings.intervalMinutes

    if (!every || seat.pausedAt || document.hidden) {
      return
    }

    let minutes
    let message

    if (seat.timerEndsAt) {
      const leftMs = Date.parse(seat.timerEndsAt) - timer.now

      if (leftMs <= 0) {
        return
      }

      minutes = Math.ceil(leftMs / 60000)
      message = '剩 ' + minutes + ' 分鐘'
    } else if (seat.timerStartedAt) {
      minutes = Math.floor(
        (timer.now - Date.parse(seat.timerStartedAt)) / 60000
      )
      message = '已經 ' + minutes + ' 分鐘'
    } else {
      return
    }

    // The first tick only sets the baseline, so switching this on (or
    // loading the page) doesn't speak immediately.
    if (lastIntervalMark === null) {
      lastIntervalMark = minutes
      return
    }

    if (minutes === lastIntervalMark) {
      return
    }

    lastIntervalMark = minutes

    if (minutes > 0 && minutes % every === 0) {
      announcer.say(message)
    }
  }

  watch(
    () => timer.now,
    () => {
      const seat = socket.mySeat()

      if (!seat || !seat.timerMode) {
        return
      }

      checkTimerEnd(seat)
      checkInterval(seat)
    }
  )

  watch(
    () => settings.intervalMinutes,
    () => {
      lastIntervalMark = null
    }
  )

  // --- room activity ---

  function neighbourPrefix(seat, mine) {
    if (mine.groupCode && seat.groupCode === mine.groupCode) {
      return '同桌的 '
    }

    if (
      !mine.groupCode &&
      !seat.groupCode &&
      Math.abs(seat.seatNumber - mine.seatNumber) === 1
    ) {
      return '隔壁的 '
    }

    return null
  }

  function flushRoomMessages() {
    roomFlushHandle = null

    if (roomMessages.length > 0) {
      announcer.say(roomMessages.join('；'))
      roomMessages = []
    }
  }

  socket.onSeatChange((seat, before) => {
    const mode = settings.roomActivity
    const mine = socket.mySeat()

    if (mode === 'off' || !mine) {
      return
    }

    const sameFloor =
      socket.floorForSeat(seat.code) === socket.floorForSeat(mine.code)

    if (!sameFloor) {
      return
    }

    const prefix = neighbourPrefix(seat, mine)

    if (mode === 'neighbors' && prefix === null) {
      return
    }

    const who = (seat.isOccupied ? seat.nickname : before.nickname) || '同學'
    const where = prefix ?? seat.label + ' 的 '

    roomMessages.push(where + who + (seat.isOccupied ? ' 入座了' : ' 離開了'))

    if (!roomFlushHandle) {
      roomFlushHandle = setTimeout(flushRoomMessages, ROOM_BATCH_MS)
    }
  })

  onUnmounted(() => {
    clearTimeout(roomFlushHandle)
  })
}
