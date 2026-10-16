// Wiki timestamps arrive as ISO-8601 strings; they're shown in Taipei time,
// like every other date on the site.
const dateFormatter = new Intl.DateTimeFormat('zh-TW', {
  timeZone: 'Asia/Taipei',
  year: 'numeric',
  month: 'long',
  day: 'numeric',
})

const dateTimeFormatter = new Intl.DateTimeFormat('zh-TW', {
  timeZone: 'Asia/Taipei',
  year: 'numeric',
  month: 'numeric',
  day: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
  hour12: false,
})

export function formatWikiDate(value) {
  return value ? dateFormatter.format(new Date(value)) : ''
}

export function formatWikiDateTime(value) {
  return value ? dateTimeFormatter.format(new Date(value)) : ''
}
