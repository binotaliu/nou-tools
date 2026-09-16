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

  function seatAriaLabel(seat) {
    if (!seat.isOccupied) {
      return seat.label + '，空位，點擊入座'
    }

    return seat.label + '，' + (seat.nickname || '同學') + ' 正在使用'
  }

  function thoughtBubbleText(seat) {
    if (!seat.timerMode) {
      return ''
    }

    if (seat.timerPhase === 'break') {
      return '休息中'
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
      : 'text-warm-500 dark:text-zinc-400'
  }

  function seatClasses(seat, variant = 'solo') {
    const classes =
      variant === 'table'
        ? [
            'relative flex size-9 items-center justify-center rounded-lg border-2 text-center transition',
          ]
        : [
            'group relative flex min-h-[99px] w-full max-w-24 flex-col items-center justify-end gap-0.5 rounded-t-lg border-x-[3px] border-t-[3px] border-warm-300 px-1 pt-6 pb-1.5 text-center transition dark:border-zinc-600',
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
          ? 'border-warm-400 bg-white shadow-sm dark:border-zinc-500 dark:bg-zinc-800'
          : 'bg-warm-100/80 dark:bg-zinc-800/80'
      )
    } else {
      classes.push(
        variant === 'table'
          ? 'border-warm-300 bg-white/70 hover:border-warm-400 hover:bg-white dark:border-zinc-600 dark:bg-zinc-800/60 dark:hover:border-zinc-500 dark:hover:bg-zinc-800'
          : 'bg-white/60 hover:bg-white hover:border-warm-400 dark:bg-zinc-900/60 dark:hover:bg-zinc-800 dark:hover:border-zinc-500'
      )
    }

    if (socket.busySeatCode === seat.code) {
      classes.push('opacity-50 cursor-wait')
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

  // --- table seat popover ---

  function tapTableSeat(seat) {
    if (!seat.isOccupied) {
      socket.take(seat.code)
      return
    }

    peekSeatCode.value = peekSeatCode.value === seat.code ? null : seat.code
  }

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
    tapTableSeat,
    peek,
    unpeek,
    isPeeking,
  })
}
