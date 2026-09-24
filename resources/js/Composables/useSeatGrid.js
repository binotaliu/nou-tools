import { reactive, ref } from 'vue'

// Seat CSS classes, aria labels, floor/stair labels, and the table-chair
// hover/tap popover. Pure display logic over the socket's `state` — the
// actual seat claim (take()) lives in useStudyRoomSocket.js, since it's a
// state mutation, not a display concern.
export default function useSeatGrid(socket, config) {
  const peekSeatCode = ref(null)

  function isMine(seat) {
    return seat.code === socket.heldSeatCode
  }

  function seatTestId(seat) {
    return 'seat-' + seat.code
  }

  // The whole seat in one sentence, since occupied seats stay focusable and
  // this is all a screen reader hears of them: who, what, and how long.
  // `spokenTimer` is timer.spokenTimerLabel(seat), rounded to minutes.
  function seatAriaLabel(seat, spokenTimer = '') {
    if (!seat.isOccupied) {
      return seat.label + '，空位'
    }

    const parts = [seat.label]

    if (isMine(seat)) {
      parts.push('你的座位', '按下移到控制列')
    } else {
      parts.push((seat.nickname || '同學') + ' 正在使用')
    }

    // The hint reads last on your own seat, after what's on it.
    const hint = isMine(seat) ? parts.splice(2, 1) : []

    parts.push(thoughtBubbleText(seat), spokenTimer, ...hint)

    return parts.filter(Boolean).join('，')
  }

  // Occupied seats and, once you hold one, every other seat are
  // aria-disabled rather than disabled, so they stay focusable: a disabled
  // button drops keyboard focus and hides its occupant from Tab. Your own
  // seat stays actionable: it leads to your control panel.
  function isSeatActionable(seat) {
    if (isMine(seat)) {
      return true
    }

    return (
      !seat.isOccupied &&
      socket.heldSeatCode === null &&
      socket.busySeatCode === null
    )
  }

  // Set by the page: what pressing your own seat does (open and focus the
  // control panel), since the panel lives outside the grid.
  let ownSeatHandler = null

  function onOwnSeatActivated(callback) {
    ownSeatHandler = callback
  }

  // Click/Enter/Space on any seat: a free one is taken, your own leads to
  // the control panel, another occupied table chair toggles its popover,
  // anything else does nothing.
  function activateSeat(seat) {
    if (isMine(seat)) {
      ownSeatHandler?.(seat)
      return
    }

    if (socket.busySeatCode !== null) {
      return
    }

    if (seat.isOccupied) {
      peekSeatCode.value = peekSeatCode.value === seat.code ? null : seat.code
      return
    }

    if (socket.heldSeatCode === null) {
      socket.take(seat.code)
    }
  }

  function thoughtBubbleText(seat) {
    if (!seat.timerMode) {
      return ''
    }

    if (seat.timerPhase === 'break') {
      return '休息中'
    }

    if (seat.pausedAt) {
      return '暫停中'
    }

    return seat.activity || '專注中'
  }

  function needsMarquee(seat) {
    const text = thoughtBubbleText(seat)

    return !!text && text.length > 8
  }

  function seatTimerLabelClass(seat) {
    return seat.timerPhase === 'break'
      ? 'text-emerald-600 dark:text-emerald-400'
      : 'text-theme-500 dark:text-zinc-400'
  }

  function seatClasses(seat, variant = 'solo') {
    const classes =
      variant === 'table'
        ? [
            'relative flex size-9 items-center justify-center rounded-lg border-2 text-center transition',
          ]
        : [
            'group relative flex min-h-[99px] w-full max-w-24 flex-col items-center justify-end gap-0.5 rounded-t-lg border-x-[3px] border-t-[3px] border-theme-300 px-1 pt-6 pb-1.5 text-center transition dark:border-zinc-600',
          ]

    if (isMine(seat)) {
      classes.push(
        variant === 'table'
          ? 'border-amber-400 bg-amber-50 shadow-sm dark:border-amber-600 dark:bg-amber-950/40'
          : 'bg-amber-50 dark:bg-amber-950/30'
      )
    } else if (seat.isOccupied) {
      classes.push(
        variant === 'table'
          ? 'border-theme-400 bg-white shadow-sm dark:border-zinc-500 dark:bg-zinc-800'
          : 'bg-theme-100/80 dark:bg-zinc-800/80'
      )
    } else {
      classes.push(
        variant === 'table'
          ? 'border-theme-300 bg-white/70 hover:border-theme-400 hover:bg-white dark:border-zinc-600 dark:bg-zinc-800/60 dark:hover:border-zinc-500 dark:hover:bg-zinc-800'
          : 'bg-white/60 hover:bg-white hover:border-theme-400 dark:bg-zinc-900/60 dark:hover:bg-zinc-800 dark:hover:border-zinc-500'
      )
    }

    if (socket.busySeatCode === seat.code) {
      classes.push('opacity-50 cursor-wait')
    } else if (!isSeatActionable(seat)) {
      classes.push('cursor-default')
    }

    // Keeps a focused seat clear of the fixed control panel.
    if (socket.heldSeatCode !== null) {
      classes.push('scroll-mb-96 sm:scroll-mb-72 lg:scroll-mb-48')
    }

    return classes.join(' ')
  }

  function tableSeatsRow(table, row) {
    const half = Math.ceil(table.seats.length / 2)

    return row === 0 ? table.seats.slice(0, half) : table.seats.slice(half)
  }

  const FLOOR_DIGITS = [
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

  function floorLabel(floor) {
    return (FLOOR_DIGITS[floor] || String(floor)) + '樓'
  }

  function isGroundFloor(floor) {
    return floor.floor === 1
  }

  function stairHint(floor) {
    const nextFloor = floor.floor + 1

    if (nextFloor > config.maxFloors) {
      return '頂樓'
    }

    return '往' + floorLabel(nextFloor)
  }

  function isStairBlocked(floor) {
    const nextFloor = floor.floor + 1

    return nextFloor <= config.maxFloors && nextFloor > socket.state.openFloors
  }

  function stairDownHint(floor) {
    return '往' + floorLabel(floor.floor - 1) + ' ↓'
  }

  // The stairs are drawn aria-hidden; this is their text alternative, only
  // needed when the way up is closed (an open floor has its own section).
  function stairSpokenHint(floor) {
    if (!isStairBlocked(floor)) {
      return ''
    }

    return floorLabel(floor.floor + 1) + '尚未開放，樓下坐滿後就會開放'
  }

  // 快速入座: the lowest open floor first, solo seats before table chairs,
  // i.e. the first free seat in reading order.
  function firstFreeSeat() {
    if (!socket.state) {
      return null
    }

    for (const floor of socket.state.floors) {
      const seat =
        floor.soloSeats.find(candidate => !candidate.isOccupied) ||
        floor.tables
          .flatMap(table => table.seats)
          .find(candidate => !candidate.isOccupied)

      if (seat) {
        return seat
      }
    }

    return null
  }

  // --- table seat popover ---

  function peek(seat) {
    if (seat.isOccupied) {
      peekSeatCode.value = seat.code
    }
  }

  function unpeek(seat) {
    if (peekSeatCode.value === seat.code) {
      peekSeatCode.value = null
    }
  }

  function isPeeking(seat) {
    return seat.isOccupied && peekSeatCode.value === seat.code
  }

  return reactive({
    peekSeatCode,
    isMine,
    seatTestId,
    seatAriaLabel,
    isSeatActionable,
    onOwnSeatActivated,
    activateSeat,
    firstFreeSeat,
    thoughtBubbleText,
    needsMarquee,
    seatTimerLabelClass,
    seatClasses,
    tableSeatsRow,
    floorLabel,
    isGroundFloor,
    stairHint,
    isStairBlocked,
    stairDownHint,
    stairSpokenHint,
    peek,
    unpeek,
    isPeeking,
  })
}
