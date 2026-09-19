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

function toDateString(year, month, day) {
  return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
}

export function addDays(value, amount) {
  const { year, month, day } = parts(value)
  const shifted = new Date(Date.UTC(year, month - 1, day + amount))

  return toDateString(
    shifted.getUTCFullYear(),
    shifted.getUTCMonth() + 1,
    shifted.getUTCDate()
  )
}

export function dateRangeDays(start, end) {
  const days = []

  for (let date = start; date <= end; date = addDays(date, 1)) {
    days.push(date)
  }

  return days
}

export function newsletterDateParts(value) {
  return parts(value)
}
