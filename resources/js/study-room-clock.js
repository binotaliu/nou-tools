function pad2(n) {
  return String(n).padStart(2, '0')
}

export function clockLabel(totalSeconds) {
  const minutes = Math.floor(totalSeconds / 60)
  const seconds = totalSeconds % 60

  if (minutes < 60) {
    return pad2(minutes) + ':' + pad2(seconds)
  }

  const hours = Math.floor(minutes / 60)

  return hours + ':' + pad2(minutes % 60) + ':' + pad2(seconds)
}

// A paused timer's clock is frozen at the moment it was paused, so its
// countdown, count-up and progress bar all stop there instead of ticking.
export function clockNow(seat, nowMs) {
  return seat.pausedAt ? Date.parse(seat.pausedAt) : nowMs
}

// Time left (or "+" overtime) of a countdown, else elapsed of a count-up.
export function remainingLabel(seat, nowMs) {
  if (seat.timerEndsAt) {
    const diffMs = Date.parse(seat.timerEndsAt) - clockNow(seat, nowMs)

    if (diffMs > 0) {
      return clockLabel(Math.ceil(diffMs / 1000))
    }

    return '+' + clockLabel(Math.floor(-diffMs / 1000))
  }

  if (seat.timerStartedAt) {
    const elapsedSeconds = Math.max(
      0,
      Math.floor(
        (clockNow(seat, nowMs) - Date.parse(seat.timerStartedAt)) / 1000
      )
    )

    return clockLabel(elapsedSeconds)
  }

  return ''
}
