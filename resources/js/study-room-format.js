// Shared "1 小時 5 分" style duration used by the stats modal and the
// progress page.
export function formatDurationLabel(totalSeconds) {
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)

  return hours > 0 ? hours + ' 小時 ' + minutes + ' 分' : minutes + ' 分'
}

export function sessionDurationLabel(session) {
  const label = formatDurationLabel(session.focusSeconds)

  if (!session.overtimeSeconds) {
    return label
  }

  return label + '（超時 ' + formatDurationLabel(session.overtimeSeconds) + '）'
}
