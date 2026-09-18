// Newsletter dates arrive as plain `Y-m-d` strings (Taipei calendar dates),
// so they're split rather than passed through `new Date()`, which would
// parse them as UTC midnight and could shift the weekday.
const WEEKDAYS = ['日', '一', '二', '三', '四', '五', '六']

function parts(value) {
  const [year, month, day] = value.split('-').map(Number)

  return {
    year,
    month,
    day,
    weekday: WEEKDAYS[new Date(Date.UTC(year, month - 1, day)).getUTCDay()],
  }
}

export function formatNewsletterDate(value) {
  if (!value) {
    return ''
  }

  const { year, month, day } = parts(value)

  return `${year} 年 ${month} 月 ${day} 日`
}

export function formatShortNewsletterDate(value) {
  if (!value) {
    return ''
  }

  const { month, day, weekday } = parts(value)

  return `${month}/${day}（${weekday}）`
}

export function formatNewsletterDateRange(start, end) {
  if (start === end) {
    return formatShortNewsletterDate(start)
  }

  return `${formatShortNewsletterDate(start)} ～ ${formatShortNewsletterDate(end)}`
}
