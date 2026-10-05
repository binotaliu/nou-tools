// Site navigation entries, shared by the header, the installed-PWA navs and
// the homepage 功能選單 launcher.

// Learning progress lives under /schedules/..., so the plain '/schedules'
// prefix would keep 我的課表 highlighted there too. Covers both the
// /schedules/my/learning-progress shortcut and
// /schedules/{schedule}/{term}/learning-progress.
const LEARNING_PROGRESS_PATH =
  /^\/schedules\/(?:my|[^/]+\/[^/]+)\/learning-progress$/

export const navItems = [
  {
    href: '/schedules/my',
    prefix: '/schedules',
    match: path =>
      path.startsWith('/schedules') && !LEARNING_PROGRESS_PATH.test(path),
    label: '我的課表',
    icon: 'table-cells',
  },
  {
    href: '/schedules/my/learning-progress',
    prefix: '/schedules/my/learning-progress',
    match: path => LEARNING_PROGRESS_PATH.test(path),
    label: '學習進度',
    icon: 'clipboard',
  },
  {
    href: '/study-room',
    prefix: '/study-room',
    label: '自習室',
    icon: 'academic-cap',
  },
  {
    href: '/newsletter',
    prefix: '/newsletter',
    label: '雙週報',
    icon: 'newspaper',
  },
  {
    href: '/discount-stores',
    prefix: '/discount-stores',
    label: '優惠店家',
    icon: 'tag',
  },
]

export const moreMenuItems = [
  {
    href: '/alt-uu',
    prefix: '/alt-uu',
    label: 'Alt UU',
    icon: 'device-phone-mobile',
  },
  {
    href: '/announcements',
    prefix: '/announcements',
    label: '學校公告',
    icon: 'megaphone',
  },
  {
    href: '/video-classes',
    prefix: '/video-classes',
    label: '今日視訊面授',
    icon: 'video-camera',
  },
  {
    href: '/school-calendar',
    prefix: '/school-calendar',
    label: '學校行事曆',
    icon: 'calendar',
  },
  {
    href: '/courses/schedule',
    prefix: '/courses/schedule',
    label: '本學期開課表',
    icon: 'calendar-days',
  },
  {
    href: '/directory',
    prefix: '/directory',
    label: '連結 / 學習指導中心目錄',
    // The more sheet's launcher tiles fit about two short lines.
    shortLabel: '連結目錄',
    icon: 'map',
    offlineAllow: true,
  },
]

export const homeItem = {
  href: '/',
  prefix: '/',
  label: '首頁',
  icon: 'book-open',
}
export const settingsItem = {
  href: '/settings',
  prefix: '/settings',
  label: '設定',
  icon: 'cog-6-tooth',
}
export const aboutItem = {
  href: '/about',
  prefix: '/about',
  label: '關於',
  icon: 'information-circle',
}
