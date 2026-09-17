<script setup>
// Shared persistent chrome (sticky header/nav + footer). Pages wrap
// themselves in it via `<AppLayout>...</AppLayout>` or the
// `defineOptions({ layout: AppLayout })` convention.
//
// Nav active-state checks use Inertia's `usePage().url`, matched against
// route paths (there is no Ziggy route() helper on the frontend).
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import Icon from '../Components/Icon.vue'
import Notification from '../Components/Notification.vue'
import ThemeSwitcherPopover from '../Components/ThemeSwitcherPopover.vue'

const page = usePage()
const currentPath = computed(() => page.url.split('?')[0])

// Toast notifications for flash messages and validation errors.
// `flash.success` is shared by HandleInertiaRequests; `errors` is Inertia's
// own default shared prop.
const successMessage = computed(() => page.props.flash?.success ?? null)
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

const mobileMenuOpen = ref(false)
const moreMenuOpen = ref(false)

const navItems = [
  {
    href: '/schedules/my',
    prefix: '/schedules',
    label: '我的課表',
    icon: 'table-cells',
  },
  {
    href: '/study-room',
    prefix: '/study-room',
    label: '自習室',
    icon: 'academic-cap',
  },
  {
    href: '/announcements',
    prefix: '/announcements',
    label: '學校公告',
    icon: 'megaphone',
  },
  {
    href: '/discount-stores',
    prefix: '/discount-stores',
    label: '優惠店家',
    icon: 'tag',
  },
  {
    href: '/alt-uu',
    prefix: '/alt-uu',
    label: 'Alt UU',
    icon: 'device-phone-mobile',
  },
]

const moreMenuItems = [
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
    icon: 'map',
    offlineAllow: true,
  },
]
</script>

<template>
  <a
    href="#main-content"
    class="skip-link absolute top-auto -left-100 z-999 bg-transparent px-2 py-1 focus:top-0 focus:left-0 focus:bg-white focus:text-theme-900 focus:ring-2 focus:ring-theme-500 dark:focus:bg-zinc-900 dark:focus:text-zinc-100"
  >
    跳到主要區塊
  </a>

  <header
    class="sticky top-0 z-40 border-b border-theme-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 print:static"
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
          <nav class="hidden items-center gap-1 gap-x-6 md:flex print:hidden">
            <Link
              v-for="item in navItems"
              :key="item.href"
              :href="item.href"
              class="-m-2 inline-flex items-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium transition-colors md:px-3"
              :class="
                isActive(item.prefix)
                  ? 'bg-theme-100 text-theme-900 dark:bg-theme-900/40 dark:text-theme-100'
                  : 'text-theme-600 hover:bg-theme-100 hover:text-theme-900 dark:text-zinc-400 dark:hover:bg-theme-900/40 dark:hover:text-theme-100'
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
                  moreMenuItems.some(item => isActive(item.prefix))
                    ? 'bg-theme-100 text-theme-900 dark:bg-theme-900/40 dark:text-theme-100'
                    : 'text-theme-600 hover:bg-theme-100 hover:text-theme-900 dark:text-zinc-400 dark:hover:bg-theme-900/40 dark:hover:text-theme-100'
                "
                :aria-expanded="moreMenuOpen.toString()"
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
                    isActive(item.prefix)
                      ? 'bg-theme-100 text-theme-900 dark:bg-theme-900/40 dark:text-theme-100'
                      : 'text-theme-600 hover:bg-theme-100 hover:text-theme-900 dark:text-zinc-400 dark:hover:bg-theme-900/40 dark:hover:text-theme-100'
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
            class="inline-flex items-center justify-center rounded-md border border-theme-200 bg-white p-2 text-theme-700 transition hover:bg-theme-50 focus:ring-2 focus:ring-theme-500 focus:outline-none md:hidden dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
            :aria-expanded="mobileMenuOpen.toString()"
            @click="mobileMenuOpen = !mobileMenuOpen"
          >
            <span class="sr-only">切換選單</span>

            <Icon v-show="!mobileMenuOpen" name="bars-3" class="size-5" />
            <Icon v-show="mobileMenuOpen" name="x-mark" class="size-5" />
          </button>
        </div>
      </div>

      <div
        v-show="mobileMenuOpen"
        class="absolute top-full right-0 left-0 -mx-px mt-0 space-y-2 rounded-b-2xl border border-theme-200 bg-white p-3 shadow-lg md:hidden dark:border-zinc-700 dark:bg-zinc-900 print:hidden"
      >
        <Link
          v-for="item in navItems"
          :key="item.href"
          :href="item.href"
          class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors"
          :class="
            isActive(item.prefix)
              ? 'bg-theme-100 text-theme-900 dark:bg-theme-900/40 dark:text-theme-100'
              : 'text-theme-600 hover:bg-theme-100 hover:text-theme-900 dark:text-zinc-400 dark:hover:bg-theme-900/40 dark:hover:text-theme-100'
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
            isActive(item.prefix)
              ? 'bg-theme-100 text-theme-900 dark:bg-theme-900/40 dark:text-theme-100'
              : 'text-theme-600 hover:bg-theme-100 hover:text-theme-900 dark:text-zinc-400 dark:hover:bg-theme-900/40 dark:hover:text-theme-100'
          "
        >
          <Icon :name="item.icon" class="size-4 shrink-0" />
          {{ item.label }}
        </Link>
      </div>
    </div>
  </header>

  <main id="main-content" class="mx-auto max-w-7xl px-6 py-8">
    <!-- flash notifications use slide-in toasts instead of the old alert box -->
    <Notification
      v-if="successMessage"
      type="success"
      :message="successMessage"
      class="print:hidden"
    />

    <!-- show first error only in toast; the page can still display the full list if needed -->
    <Notification
      v-if="firstErrorMessage"
      type="error"
      :message="firstErrorMessage"
      class="print:hidden"
    />

    <slot />
  </main>

  <footer
    class="mt-12 border-t border-theme-200 bg-theme-100 py-8 text-theme-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 print:bg-white print:text-black"
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
        class="flex flex-col items-center justify-between gap-10 md:flex-row md:gap-6 print:hidden"
      >
        <div class="flex flex-col items-center gap-1 md:flex-row md:gap-4">
          <div class="p-3">
            <Icon
              name="book-open"
              class="size-6 text-theme-700 dark:text-zinc-300"
            />
          </div>

          <div class="text-center md:text-left">
            <Link
              href="/"
              class="text-lg font-semibold text-theme-700 hover:text-theme-900 dark:text-zinc-300 dark:hover:text-zinc-100"
            >
              NOU 小幫手
            </Link>
            <p class="mt-1 text-xs text-theme-500 dark:text-zinc-400">
              給 NOU 同學的非官方小工具
            </p>
          </div>
        </div>

        <div class="flex flex-col items-center gap-6 sm:flex-row">
          <div
            class="max-w-lg text-center text-sm text-theme-400 md:text-left dark:text-zinc-500"
          >
            <span class="font-semibold">免責聲明：</span>
            <p class="mb-2 text-justify text-xs md:text-left">
              本網站為學生自發製作之工具，僅供同學參考使用，並非學校官方發布；所有資訊以學校正式公告為準；本網站已盡可能提供準確資訊，但不保證其完整性或正確性；針對重要資訊，請使用者自行查證並以學校官方公告為準；課程相關資訊係搜集整理自學校官方公告、網站，與其他官方資料，採用合理使用原則提供同學參考使用；使用本網站即表示同意此免責聲明之內容。
            </p>
            <span class="font-semibold">開放原始碼授權聲明：</span>
            <p class="text-justify text-xs md:text-left">
              本網站是自由且開放原始碼之軟體，使用 AGPL-3.0
              授權條款。歡迎各位同學自由審閱、修改、使用、再散佈本網站原始碼，但請遵守
              AGPL
              授權條款。如果您以任何形式參考了本網站之原始碼並開發了新的軟體，則此一沿伸軟體也必須使用與遵守
              AGPL
              授權條款，請在閱讀、參考、引用本網站原始碼時特別注意授權問題。
            </p>
          </div>
        </div>
      </div>

      <div
        class="mt-6 flex flex-col-reverse items-center justify-between gap-6 border-t border-theme-200 pt-4 text-xs text-theme-500 md:flex-row md:gap-3 dark:border-zinc-700 dark:text-zinc-400 print:hidden"
      >
        <div>&copy; {{ new Date().getFullYear() }} NOU 小幫手</div>
        <div class="flex items-center gap-x-8 gap-y-2">
          <div class="text-xs">
            <a
              href="https://kuma.binota.org/status/nou"
              class="inline-flex items-center gap-1 text-theme-500 hover:text-theme-600 dark:text-zinc-400 dark:hover:text-zinc-300"
              target="_blank"
              rel="noopener noreferrer"
            >
              <Icon name="computer-desktop" class="size-3" />
              學校網站狀態
            </a>
          </div>
          <div class="text-xs">
            <a
              href="https://github.com/binotaliu/nou-tools"
              class="inline-flex items-center gap-1 text-theme-500 hover:text-theme-600 dark:text-zinc-400 dark:hover:text-zinc-300"
              target="_blank"
              rel="noopener noreferrer"
            >
              <Icon name="code-bracket" class="size-3" />
              網站原始碼
            </a>
          </div>
          <div class="text-xs">
            <a
              href="mailto:nou-tools-contact@binota.org"
              class="inline-flex items-center gap-1 text-theme-500 hover:text-theme-600 dark:text-zinc-400 dark:hover:text-zinc-300"
            >
              <Icon name="envelope" class="size-3" />
              聯絡作者
            </a>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>
