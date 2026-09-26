<script setup>
// Shared persistent chrome (sticky header/nav + footer). Pages wrap
// themselves in it via `<AppLayout>...</AppLayout>` or the
// `defineOptions({ layout: AppLayout })` convention.
//
// Nav active-state checks use Inertia's `usePage().url`, matched against
// route paths (there is no Ziggy route() helper on the frontend).
import { computed, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AdaptableNav from '../Components/AdaptableNav.vue'
import BottomNav from '../Components/BottomNav.vue'
import CookieConsentBanner from '../Components/CookieConsentBanner.vue'
import Icon from '../Components/Icon.vue'
import Notification from '../Components/Notification.vue'

// Room for a fixed bottom banner (study room's ActionBanner) to sit over
// without hiding content. It goes on the footer; only the installed phone
// PWA, whose footer is hidden, puts it on <main> instead.
defineProps({ reserveBottomSpace: { type: Boolean, default: false } })
import ThemeSwitcherPopover from '../Components/ThemeSwitcherPopover.vue'

const page = usePage()

const footerLinkClass =
  'text-theme-700 hover:text-theme-900 hover:underline dark:text-zinc-400 dark:hover:text-zinc-100'
const currentPath = computed(() => page.url.split('?')[0])

// Toast notifications for flash messages and validation errors.
// `flash.success` is shared by HandleInertiaRequests; `errors` is Inertia's
// own default shared prop.
const successMessage = computed(() => page.props.flash?.success ?? null)

// Every Inertia response replaces `flash`/`errors` with new objects, even when
// the text is identical. A toast hides itself after a few seconds, so without
// a per-response key a repeat of the same message (saving twice on one page)
// would reuse the already-hidden component and never show again.
const toastKey = ref(0)
watch(
  () => [page.props.flash, page.props.errors],
  () => {
    toastKey.value++
  }
)
const firstErrorMessage = computed(() => {
  const errors = page.props.errors ?? {}
  const keys = Object.keys(errors)

  return keys.length ? errors[keys[0]] : null
})

function isActive(prefix) {
  return (
    currentPath.value === prefix || currentPath.value.startsWith(prefix + '/')
  )
}

// Learning progress lives under /schedules/..., so the plain '/schedules'
// prefix would keep 我的課表 highlighted there too. Covers both the
// /schedules/my/learning-progress shortcut and
// /schedules/{schedule}/{term}/learning-progress.
const LEARNING_PROGRESS_PATH =
  /^\/schedules\/(?:my|[^/]+\/[^/]+)\/learning-progress$/

// Items may carry their own `match(path)`; otherwise the route prefix decides.
function isItemActive(item) {
  return item.match ? item.match(currentPath.value) : isActive(item.prefix)
}

const mobileMenuOpen = ref(false)
const moreMenuOpen = ref(false)

const navItems = [
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

const moreMenuItems = [
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

const homeItem = { href: '/', prefix: '/', label: '首頁', icon: 'book-open' }
const settingsItem = {
  href: '/settings',
  prefix: '/settings',
  label: '設定',
  icon: 'cog-6-tooth',
}
const aboutItem = {
  href: '/about',
  prefix: '/about',
  label: '關於',
  icon: 'information-circle',
}

const withActive = item => ({
  ...item,
  active: item.prefix === '/' ? currentPath.value === '/' : isItemActive(item),
})

// Installed-PWA bottom tab bar (see BottomNav.vue): the first four primary
// links become tabs (優惠店家, the fifth, moves into its "更多" sheet along
// with everything else). The header is hidden there, so 設定 (which holds the
// theme controls the header popover offers) is reachable only from that sheet.
const bottomTabs = computed(() =>
  navItems.slice(0, 4).map(item => ({ ...item, active: isItemActive(item) }))
)
const bottomMoreItems = computed(() =>
  [
    homeItem,
    ...navItems.slice(4),
    ...moreMenuItems,
    settingsItem,
    aboutItem,
  ].map(withActive)
)

// Installed-PWA tablet/desktop nav (see AdaptableNav.vue): all five primary
// links as tabs, the rest as overflow.
const adaptablePrimary = computed(() => navItems.map(withActive))
const adaptableMore = computed(() => moreMenuItems.map(withActive))
const adaptableOther = computed(() =>
  [homeItem, settingsItem, aboutItem].map(withActive)
)
</script>

<template>
  <a
    href="#main-content"
    accesskey="1"
    class="skip-link absolute top-auto -left-100 z-999 bg-transparent px-2 py-1 focus:top-0 focus:left-0 focus:bg-white focus:text-theme-900 focus:ring-2 focus:ring-theme-500 dark:focus:bg-zinc-900 dark:focus:text-zinc-100"
  >
    跳到主要區塊
  </a>
  <Link
    href="/accessibility"
    accesskey="0"
    data-testid="skip-link-accessibility"
    class="skip-link absolute top-auto -left-100 z-999 bg-transparent px-2 py-1 focus:top-0 focus:left-0 focus:bg-white focus:text-theme-900 focus:ring-2 focus:ring-theme-500 dark:focus:bg-zinc-900 dark:focus:text-zinc-100"
  >
    無障礙說明
  </Link>
  <!-- Key target only: the header nav is display:none below lg (and in PWAs),
       and a hidden element cannot take an accesskey. Off-screen, out of the
       tab order and hidden from assistive tech, since the real nav link is
       already reachable. -->
  <Link
    href="/schedules/my"
    accesskey="2"
    tabindex="-1"
    aria-hidden="true"
    data-testid="accesskey-my-schedule"
    class="sr-only"
  >
    我的課表
  </Link>

  <header
    data-testid="site-header"
    class="sticky top-0 z-40 border-b border-theme-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 print:static bottom-nav:hidden wide-pwa:hidden"
  >
    <div class="relative mx-auto max-w-7xl px-3 py-2 md:px-6 md:py-4">
      <div class="flex items-center justify-between">
        <h1
          class="inline-flex items-center gap-2 pb-0 text-lg font-bold text-theme-700 md:gap-4 md:text-2xl dark:text-zinc-300"
        >
          <Icon
            name="book-open"
            class="size-5 shrink-0 text-theme-700 md:size-6 dark:text-zinc-300"
          />
          <Link href="/" class="shrink-0">NOU 小幫手</Link>
        </h1>

        <div class="flex min-h-9.5 items-center gap-2">
          <nav
            aria-label="主要導覽"
            data-testid="header-nav"
            class="hidden flex-wrap items-center justify-end gap-1 gap-x-6 lg:flex print:hidden bottom-nav:hidden"
          >
            <Link
              v-for="item in navItems"
              :key="item.href"
              :href="item.href"
              :aria-current="isItemActive(item) ? 'page' : null"
              class="-m-2 inline-flex items-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium transition-colors md:px-3"
              :class="
                isItemActive(item)
                  ? 'bg-theme-100 text-theme-900 dark:bg-theme-900/40 dark:text-theme-100'
                  : 'text-theme-700 hover:bg-theme-100 hover:text-theme-900 dark:text-zinc-400 dark:hover:bg-theme-900/40 dark:hover:text-theme-100'
              "
            >
              <Icon :name="item.icon" class="size-4 shrink-0" />
              <span class="hidden sm:inline">{{ item.label }}</span>
            </Link>

            <div class="relative -mt-px" @click.self="moreMenuOpen = false">
              <button
                type="button"
                class="-m-2 inline-flex items-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium transition-colors md:px-3"
                :class="
                  moreMenuItems.some(item => isItemActive(item))
                    ? 'bg-theme-100 text-theme-900 dark:bg-theme-900/40 dark:text-theme-100'
                    : 'text-theme-700 hover:bg-theme-100 hover:text-theme-900 dark:text-zinc-400 dark:hover:bg-theme-900/40 dark:hover:text-theme-100'
                "
                :aria-expanded="moreMenuOpen.toString()"
                aria-controls="header-more-menu"
                @click="moreMenuOpen = !moreMenuOpen"
              >
                <span class="hidden sm:inline">更多</span>
                <Icon
                  name="chevron-down"
                  class="size-4 shrink-0 transition-transform"
                  :class="moreMenuOpen ? 'rotate-180' : ''"
                />
              </button>

              <div
                id="header-more-menu"
                v-show="moreMenuOpen"
                class="absolute top-full right-0 z-10 mt-2 w-60 space-y-1 rounded-md border border-theme-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
              >
                <Link
                  v-for="item in moreMenuItems"
                  :key="item.href"
                  :href="item.href"
                  :data-offline-allow="item.offlineAllow ? '' : null"
                  class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                  :class="
                    isItemActive(item)
                      ? 'bg-theme-100 text-theme-900 dark:bg-theme-900/40 dark:text-theme-100'
                      : 'text-theme-700 hover:bg-theme-100 hover:text-theme-900 dark:text-zinc-400 dark:hover:bg-theme-900/40 dark:hover:text-theme-100'
                  "
                >
                  <Icon :name="item.icon" class="size-4 shrink-0" />
                  {{ item.label }}
                </Link>
              </div>
            </div>
          </nav>

          <ThemeSwitcherPopover />

          <button
            type="button"
            data-testid="header-menu-toggle"
            class="inline-flex items-center justify-center rounded-md border border-theme-200 bg-white p-2 text-theme-700 transition hover:bg-theme-50 focus:ring-2 focus:ring-theme-500 focus:outline-none lg:hidden dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 bottom-nav:hidden"
            :aria-expanded="mobileMenuOpen.toString()"
            aria-controls="header-mobile-menu"
            @click="mobileMenuOpen = !mobileMenuOpen"
          >
            <span class="sr-only">切換選單</span>

            <Icon v-show="!mobileMenuOpen" name="bars-3" class="size-5" />
            <Icon v-show="mobileMenuOpen" name="x-mark" class="size-5" />
          </button>
        </div>
      </div>

      <div
        id="header-mobile-menu"
        v-show="mobileMenuOpen"
        class="absolute top-full right-0 left-0 -mx-px mt-0 space-y-2 rounded-b-2xl border border-theme-200 bg-white p-3 shadow-lg lg:hidden dark:border-zinc-700 dark:bg-zinc-900 print:hidden bottom-nav:hidden"
      >
        <Link
          v-for="item in navItems"
          :key="item.href"
          :href="item.href"
          class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors"
          :class="
            isItemActive(item)
              ? 'bg-theme-100 text-theme-900 dark:bg-theme-900/40 dark:text-theme-100'
              : 'text-theme-700 hover:bg-theme-100 hover:text-theme-900 dark:text-zinc-400 dark:hover:bg-theme-900/40 dark:hover:text-theme-100'
          "
        >
          <Icon :name="item.icon" class="size-4 shrink-0" />
          {{ item.label }}
        </Link>

        <Link
          v-for="item in moreMenuItems"
          :key="item.href"
          :href="item.href"
          :data-offline-allow="item.offlineAllow ? '' : null"
          class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors"
          :class="
            isItemActive(item)
              ? 'bg-theme-100 text-theme-900 dark:bg-theme-900/40 dark:text-theme-100'
              : 'text-theme-700 hover:bg-theme-100 hover:text-theme-900 dark:text-zinc-400 dark:hover:bg-theme-900/40 dark:hover:text-theme-100'
          "
        >
          <Icon :name="item.icon" class="size-4 shrink-0" />
          {{ item.label }}
        </Link>
      </div>
    </div>
  </header>

  <!-- Before <main>, not with BottomNav: its tab bar is sticky at the top, so
       its place in the flow is its place on the page. -->
  <AdaptableNav
    :primary="adaptablePrimary"
    :more="adaptableMore"
    :other="adaptableOther"
    :current-path="currentPath"
  />

  <main
    id="main-content"
    tabindex="-1"
    :class="
      reserveBottomSpace
        ? 'pwa:pb-[calc(var(--pwa-nav-height)+26rem)] sm:pwa:pb-[calc(var(--pwa-nav-height)+20rem)] lg:pwa:pb-48'
        : 'pwa:pb-[calc(var(--pwa-nav-height)+2rem)]'
    "
    class="mx-auto max-w-7xl px-6 py-8 focus:outline-none"
  >
    <!-- flash notifications use slide-in toasts instead of the old alert box -->
    <Notification
      v-if="successMessage"
      :key="`success-${toastKey}`"
      type="success"
      :message="successMessage"
      class="print:hidden"
    />

    <!-- show first error only in toast; the page can still display the full list if needed -->
    <Notification
      v-if="firstErrorMessage"
      :key="`error-${toastKey}`"
      type="error"
      :message="firstErrorMessage"
      class="print:hidden"
    />

    <slot />
  </main>

  <footer
    :class="reserveBottomSpace ? 'pb-96! sm:pb-72! lg:pb-48!' : ''"
    class="mt-12 border-t border-theme-200 bg-theme-100 py-8 text-theme-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 print:bg-white print:text-black bottom-nav:hidden wide-pwa:hidden"
    data-testid="site-footer"
  >
    <div class="mx-auto max-w-7xl px-6">
      <div class="hidden py-2 text-center text-xs text-theme-800 print:block">
        <p class="mb-1">
          &copy; {{ new Date().getFullYear() }} NOU 小幫手 —
          {{ typeof window !== 'undefined' ? window.location.origin : '' }}
          <br />
          免責聲明：本網站為學生自發製作之工具，僅供參考，請以學校正式公告為準。
        </p>
        <p class="text-xs">
          網站原始碼：https://github.com/binotaliu/nou-tools
          <br />
          聯絡網站作者：nou-tools-contact@binota.org
        </p>
      </div>

      <div
        class="flex flex-col items-center justify-between gap-8 md:flex-row md:items-center md:gap-12 print:hidden"
      >
        <div class="flex items-center gap-3">
          <Icon
            name="book-open"
            class="size-6 text-theme-700 dark:text-zinc-300"
          />
          <div class="text-center md:text-left">
            <Link
              href="/"
              class="text-lg font-semibold text-theme-700 hover:text-theme-900 dark:text-zinc-300 dark:hover:text-zinc-100"
            >
              NOU 小幫手
            </Link>
            <p class="text-xs text-theme-700 dark:text-zinc-400">
              給 NOU 同學的非官方小工具
            </p>
          </div>
        </div>

        <nav
          aria-label="頁尾連結"
          class="grid shrink-0 grid-cols-2 gap-x-12 gap-y-6 text-sm text-theme-700 dark:text-zinc-400"
        >
          <div>
            <h2
              class="mb-2 text-xs font-semibold text-theme-900 dark:text-zinc-100"
            >
              關於本站
            </h2>
            <ul class="space-y-2">
              <li>
                <Link href="/about" :class="footerLinkClass">關於</Link>
              </li>
              <li>
                <Link href="/changelog" :class="footerLinkClass">
                  更新日誌
                </Link>
              </li>
              <li>
                <Link
                  href="/accessibility"
                  data-testid="footer-accessibility-link"
                  :class="footerLinkClass"
                >
                  無障礙說明
                </Link>
              </li>
            </ul>
          </div>
          <div>
            <h2
              class="mb-2 text-xs font-semibold text-theme-900 dark:text-zinc-100"
            >
              狀態與聯絡
            </h2>
            <ul class="space-y-2">
              <li>
                <a
                  href="https://kuma.binota.org/status/nou"
                  :class="footerLinkClass"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  學校網站狀態
                </a>
              </li>
              <li>
                <a
                  href="https://github.com/binotaliu/nou-tools"
                  :class="footerLinkClass"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  網站原始碼
                </a>
              </li>
              <li>
                <a
                  href="mailto:nou-tools-contact@binota.org"
                  :class="footerLinkClass"
                >
                  聯絡作者
                </a>
              </li>
            </ul>
          </div>
        </nav>
      </div>

      <div
        class="mt-6 flex flex-col items-center justify-between gap-2 border-t border-theme-200 pt-4 text-center text-xs text-theme-700 md:flex-row md:gap-6 dark:border-zinc-700 dark:text-zinc-400 print:hidden"
      >
        <p data-testid="footer-disclaimer" class="md:text-left">
          本網站為學生自發製作之工具，非學校官方發布，資訊僅供參考，請以學校正式公告為準。
          <Link
            href="/about#about-disclaimer"
            class="underline hover:text-theme-900 dark:hover:text-zinc-100"
          >
            完整免責聲明
          </Link>
        </p>
        <div>&copy; {{ new Date().getFullYear() }} NOU 小幫手</div>
      </div>
    </div>
  </footer>

  <BottomNav
    :tabs="bottomTabs"
    :more-items="bottomMoreItems"
    :current-path="currentPath"
  />

  <CookieConsentBanner />
</template>
