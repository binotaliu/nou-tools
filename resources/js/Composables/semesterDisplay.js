// Port of the `Str::toSemesterDisplay()` macro (app/Providers/AppServiceProvider.php).
export function toSemesterDisplay(semester) {
  const match = /^(\d{4})([ABC])$/.exec(semester ?? '')

  if (!match) {
    return semester
  }

  const rocYear = Number(match[1]) - 1911
  const termName = { A: '上學期', B: '下學期', C: '暑期' }[match[2]]

  return `${rocYear} 學年度${termName}`
}
