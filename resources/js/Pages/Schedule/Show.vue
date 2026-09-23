<script setup>
// The page's own small widgets (remember-schedule modal, push notification
// toggle, "Add to bookmarks" hint, copy-link box) use the
// usePwaStandalone/usePushSubscription/useCopyLink composables. The item
// table's row-sorting/"next class" logic is inline here rather than a
// shared composable, since it's only used on this page — mirroring how
// Courses/Schedule.vue inlines its one-off logic.
//
// NOTE: "Add to Home Screen" installs the site-wide PWA manifest, not a
// schedule-specific shortcut: the root template renders one static manifest
// link that isn't easily overridden per-page without risking a
// duplicate/conflicting <link> tag.
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import Greeting from '../../Components/Greeting.vue'
import CommonLinks from '../../Components/CommonLinks.vue'
import SchoolCalendar from '../../Components/SchoolCalendar.vue'
import AnnouncementsWidget from '../../Components/AnnouncementsWidget.vue'
import ClassCode from '../../Components/ClassCode.vue'
import ClassDates from '../../Components/Schedule/ClassDates.vue'
import PwaInstallBanner from '../../Components/PwaInstallBanner.vue'
import usePwaStandalone from '../../Composables/usePwaStandalone'
import usePushSubscription from '../../Composables/usePushSubscription'
import useCopyLink from '../../Composables/useCopyLink'
import useSchedulePdfShare from '../../Composables/useSchedulePdfShare'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
  shouldPromptRememberSchedule: {
    type: Boolean,
    required: true,
  },
  isLinkedSchedule: {
    type: Boolean,
    required: true,
  },
  classRemindersEnabled: {
    type: Boolean,
    default: false,
  },
  vapidPublicKey: {
    type: String,
    default: null,
  },
  schoolCalendar: {
    type: Object,
    required: true,
  },
  announcementsWidget: {
    type: Object,
    required: true,
  },
  shareUrl: {
    type: String,
    required: true,
  },
  qrCodeSvg: {
    type: String,
    required: true,
  },
  greeting: {
    type: Object,
    required: true,
  },
})

const csrfToken =
  typeof document !== 'undefined'
    ? (document.querySelector('meta[name="csrf-token"]')?.content ?? '')
    : ''

// Port of the Str::toSemesterDisplay()/toShortSemesterDisplay() macros
// (app/Providers/AppServiceProvider.php).
function toSemesterDisplay(semester) {
  const match = /^(\d{4})([ABC])$/.exec(semester ?? '')

  if (!match) {
    return semester
  }

  const rocYear = Number(match[1]) - 1911
  const termName = { A: '上學期', B: '下學期', C: '暑期' }[match[2]]

  return `${rocYear} 學年度${termName}`
}

function toShortSemesterDisplay(semester) {
  const match = /^(\d{4})([ABC])$/.exec(semester ?? '')

  if (!match) {
    return semester
  }

  const rocYear = Number(match[1]) - 1911
  const termName = { A: '上學期', B: '下學期', C: '暑期' }[match[2]]

  return `${rocYear} ${termName}`
}

const pageTitle = computed(
  () => `${props.viewModel.name || '我的課表'} - NOU 小幫手`
)

// The printed calendars can start their weeks on Monday or Sunday; the print
// button opens a menu to pick one.
const printOptions = [
  { weekStart: 'monday', label: '一週從週一開始' },
  { weekStart: 'sunday', label: '一週從週日開始' },
]
const printOpen = ref(false)
const printMenu = ref(null)
const pdfShare = useSchedulePdfShare()

function printUrl(option) {
  return `/schedules/${props.viewModel.uuid}/print.pdf?term=${props.viewModel.selectedTerm}&week_start=${option.weekStart}`
}

// In an installed PWA the link would open the PDF inside the app with no way
// back (iOS has no back button), so hand the file to the share sheet instead.
// Everywhere else the link works as a plain new-tab link.
function onPrintOption(event, option) {
  printOpen.value = false

  if (!pdfShare.canHandle()) {
    return
  }

  event.preventDefault()
  pdfShare.start(
    printUrl(option),
    `nou-schedule-${props.viewModel.selectedTerm}.pdf`
  )
}

function onPrintButton() {
  if (pdfShare.state.value === 'ready') {
    pdfShare.share()
  } else if (pdfShare.state.value !== 'loading') {
    printOpen.value = !printOpen.value
  }
}

const hasCourses = computed(() => props.viewModel.items.length > 0)

const hasTentative = computed(() =>
  props.viewModel.items.some(item => item.courseClass?.isTentative)
)

const pendingItems = computed(() =>
  props.viewModel.items.filter(item => item.courseClassId === null)
)

const hasPending = computed(() => pendingItems.value.length > 0)

// --- offline banner (one-off port of the `$store.network.offline` usage on
// this page; see Directory/Index.vue for the same one-off pattern) ---
const offline = ref(typeof navigator !== 'undefined' && !navigator.onLine)

function handleOnline() {
  offline.value = false
}

function handleOffline() {
  offline.value = true
}

onMounted(() => {
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
})

onUnmounted(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
})

// --- remember-schedule modal ---
const showRememberModal = ref(true)

// --- "add to bookmarks" hint ---
const { isPwa } = usePwaStandalone()

// --- push notification toggle (only rendered for a linked schedule) ---
const push = props.isLinkedSchedule
  ? usePushSubscription({
      vapidPublicKey: props.vapidPublicKey,
      subscribeUrl: `/schedules/${props.viewModel.uuid}/push-subscriptions`,
      unsubscribeUrl: `/schedules/${props.viewModel.uuid}/push-subscriptions`,
      enabled: props.classRemindersEnabled,
    })
  : null

// --- installed-PWA phone: collapse the action buttons into one menu ---
// The buttons and the push switch are hidden by CSS in that case (the
// `bottom-nav:` variant), so this menu only ever opens where they are.
const actionsOpen = ref(false)
const actionsMenu = ref(null)

function closeActions() {
  actionsOpen.value = false
}

function onActionsPointerDown(event) {
  if (actionsOpen.value && !actionsMenu.value?.contains(event.target)) {
    closeActions()
  }

  if (printOpen.value && !printMenu.value?.contains(event.target)) {
    printOpen.value = false
  }
}

function onActionsKeydown(event) {
  if (event.key !== 'Escape') {
    return
  }

  if (actionsOpen.value) {
    closeActions()
  }

  if (printOpen.value) {
    printOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('pointerdown', onActionsPointerDown)
  document.addEventListener('keydown', onActionsKeydown)
})

onUnmounted(() => {
  document.removeEventListener('pointerdown', onActionsPointerDown)
  document.removeEventListener('keydown', onActionsKeydown)
})

// --- copy share link ---
const shareInput = ref(null)
const {
  shareUrl: copyShareUrl,
  copied,
  copy,
} = useCopyLink({ shareUrl: props.shareUrl }, shareInput)

// --- schedule items table (port of resources/js/schedule-items.js /
// nouToolsScheduleItems) ---
const itemsPayload = computed(() =>
  props.viewModel.items
    .filter(item => item.courseClass !== null)
    .map(item => {
      const courseClass = item.courseClass
      const schedules = courseClass.schedules.map(schedule => {
        const effectiveStart = schedule.startTime || courseClass.startTime
        const effectiveEnd = schedule.endTime || courseClass.endTime
        const ymd = schedule.date.slice(0, 10)

        return {
          ymd,
          startTime: effectiveStart,
          endTime: effectiveEnd,
          hasOverride: schedule.startTime !== null,
          instantStart: taipeiIso(ymd, effectiveStart),
          instantEnd: taipeiIso(ymd, effectiveEnd || effectiveStart),
        }
      })

      return {
        courseName: courseClass.courseName,
        code: courseClass.isTentative ? '尚未分班' : courseClass.code,
        isTentative: courseClass.isTentative,
        teacherName: courseClass.teacherName,
        courseInfoUrl: `/courses/${courseClass.courseId}`,
        videoLink: courseClass.link,
        backupClassroomUrl: courseClass.backupClassroomUrl,
        schedules,
      }
    })
)

function taipeiIso(ymd, time) {
  // Taipei is a fixed UTC+8 offset (no DST), so this can be built directly
  // without a timezone library.
  return `${ymd}T${time || '00:00'}:00+08:00`
}

const now = ref(Date.now())
let nowInterval = null

function refreshNow() {
  now.value = Date.now()
}

function onVisibilityChange() {
  if (!document.hidden) {
    refreshNow()
  }
}

onMounted(() => {
  nowInterval = setInterval(refreshNow, 60000)
  document.addEventListener('visibilitychange', onVisibilityChange)
})

onUnmounted(() => {
  if (nowInterval) {
    clearInterval(nowInterval)
  }
  document.removeEventListener('visibilitychange', onVisibilityChange)
})

// Earliest class that has not yet ended, or null when none remain.
function nextOf(item) {
  let best = null
  let bestStart = Infinity

  for (const schedule of item.schedules) {
    if (Date.parse(schedule.instantEnd) < now.value) {
      continue
    }

    const start = Date.parse(schedule.instantStart)

    if (start < bestStart) {
      bestStart = start
      best = schedule
    }
  }

  return best
}

// Items sorted by their next class; those without an upcoming class fall to
// the bottom. `i` keeps the sort stable and keys the v-for.
const itemRows = computed(() =>
  itemsPayload.value
    .map((item, i) => ({ item, next: nextOf(item), i }))
    .sort((a, b) => {
      const aStart = a.next ? Date.parse(a.next.instantStart) : Infinity
      const bStart = b.next ? Date.parse(b.next.instantStart) : Infinity

      return aStart === bStart ? a.i - b.i : aStart - bStart
    })
)

// Split a teacher name so a trailing "老師" can render smaller, mirroring
// the previous server-side markup.
function teacher(item) {
  const name = item.teacherName

  if (!name) {
    return null
  }

  return name.endsWith('老師')
    ? { base: name.slice(0, -2), suffix: '老師' }
    : { base: name, suffix: '' }
}

function taipeiDate(next) {
  const T = window.NouTime
  return `${T.monthDay(next.ymd)} (${T.weekdayFromYmd(next.ymd)})`
}

function monthDay(next) {
  return window.NouTime.monthDay(next.ymd)
}

function weekday(next) {
  return window.NouTime.weekdayFromYmd(next.ymd)
}

// A class is "in progress" between its start and end instants; a class with
// no time set has no window to be in.
function isOngoing(next) {
  return (
    Boolean(next.startTime) &&
    Date.parse(next.instantStart) <= now.value &&
    now.value < Date.parse(next.instantEnd)
  )
}

function taipeiTime(next) {
  return next.startTime ? `${next.startTime} ~ ${next.endTime}` : null
}

// Secondary "your time" line — only when the viewer's zone differs from
// Taipei. Includes the local date when it lands on a different day than
// the Taipei date.
function localHint(next) {
  if (!next.startTime) {
    return null
  }

  const T = window.NouTime
  const start = new Date(next.instantStart)
  const end = new Date(next.instantEnd)

  if (!T.differsFromTaipei(start)) {
    return null
  }

  let datePrefix = ''
  const localStartYmd = T.localYmd(start)

  if (localStartYmd !== next.ymd) {
    datePrefix = `${T.monthDay(localStartYmd)} (${T.weekdayFromYmd(localStartYmd)}) `
  }

  return `你的時間 · ${datePrefix}${T.localHM(start)} ~ ${T.localHM(end)} (${T.gmtLabel(start)})`
}
</script>

<template>
  <Head :title="pageTitle" />

  <AppLayout>
    <div class="mx-auto max-w-5xl">
      <PwaInstallBanner />

      <div
        v-show="offline"
        class="mb-6 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
        role="status"
        aria-live="polite"
      >
        <Icon name="signal-slash" class="mt-0.5 size-5 shrink-0" />
        <div>
          <p class="font-semibold">目前處於離線狀態</p>
          <p class="mt-1">
            這是先前載入過的快取內容，可能不是最新資料。可能是 NOU
            小幫手網站發生問題，或你的裝置目前連不上網路，因此有部分的功能無法使用。本頁視訊上課連結仍可正常使用。
          </p>
        </div>
      </div>

      <div
        v-if="shouldPromptRememberSchedule && showRememberModal && !offline"
        data-testid="remember-schedule-modal-wrapper"
      >
        <div
          data-testid="remember-schedule-modal"
          class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:items-center sm:p-0"
        >
          <div
            class="fixed inset-0 bg-black/40"
            @click="showRememberModal = false"
          ></div>

          <div
            role="dialog"
            aria-modal="true"
            class="relative max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto rounded-lg bg-white p-6 shadow-lg sm:max-h-[calc(100vh-2rem)] dark:bg-zinc-900"
          >
            <h3
              class="mb-2 text-lg font-semibold text-theme-900 dark:text-zinc-100"
            >
              要記住這個課表嗎？
            </h3>
            <p class="mb-4 text-sm text-theme-700 dark:text-zinc-400">
              看樣子這個課表不是在此瀏覽器上建立的。要將此課表記住在此瀏覽器上嗎？記住後仍可使用其他瀏覽器或裝置開啟課表。
            </p>

            <form
              method="POST"
              :action="`/schedules/${viewModel.uuid}/remember`"
              class="flex justify-end gap-2"
            >
              <input type="hidden" name="_token" :value="csrfToken" />
              <button
                type="button"
                data-testid="remember-schedule-dismiss"
                data-analytics-event="remember_schedule_dismiss"
                data-analytics-feature="schedule"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
                @click="showRememberModal = false"
              >
                不用了
              </button>
              <button
                type="submit"
                data-testid="remember-schedule-confirm"
                data-analytics-event="remember_schedule_confirm"
                data-analytics-feature="schedule"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-4 py-2 font-semibold text-white transition hover:bg-theme-800"
              >
                記住課表
              </button>
            </form>
          </div>
        </div>
      </div>

      <div
        class="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row"
      >
        <div>
          <h2
            data-testid="schedule-title"
            class="mb-2 text-3xl font-bold text-theme-900 dark:text-zinc-100"
          >
            {{ viewModel.name || '我的課表' }}
          </h2>
          <p
            v-show="!isPwa"
            class="mt-1 flex items-center gap-1 text-sm text-theme-700 dark:text-zinc-400"
          >
            <Icon name="information-circle" class="inline size-4" />
            小提示：將此頁加入瀏覽器書籤，下次即可快速開啟課表。
          </p>
        </div>

        <div class="flex w-full flex-col items-end gap-2 lg:w-auto">
          <div
            class="flex w-full flex-col-reverse gap-2 sm:flex-row lg:w-auto bottom-nav:hidden"
          >
            <div class="flex w-full shrink-0 gap-2 sm:w-1/2 lg:w-auto">
              <Link
                :href="`/schedules/${viewModel.uuid}/edit?term=${viewModel.selectedTerm}`"
                data-analytics-event="schedule_edit"
                data-analytics-feature="schedule"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 sm:w-1/2 lg:w-auto dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
              >
                <Icon name="pencil-square" class="size-4" />
                編輯
              </Link>

              <Link
                :href="`/schedules/${viewModel.uuid}/customize`"
                data-analytics-event="schedule_customize_open"
                data-analytics-feature="schedule"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 sm:w-1/2 lg:w-auto dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
              >
                <Icon name="cog-6-tooth" class="size-4" />
                自訂
              </Link>
            </div>

            <Link
              :href="`/schedules/${viewModel.uuid}/${viewModel.selectedTerm}/learning-progress`"
              data-analytics-event="learning_progress_open"
              data-analytics-feature="learning_progress"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 sm:w-1/2 lg:w-auto dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
            >
              <Icon name="clipboard" class="size-4" />
              學習進度表
            </Link>

            <Link
              :href="`/schedules/${viewModel.uuid}/subscribe`"
              data-analytics-event="calendar_subscribe_open"
              data-analytics-feature="schedule"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-4 py-2 font-semibold text-white transition hover:bg-theme-800 sm:w-1/2 lg:w-auto"
            >
              <Icon name="calendar" class="inline size-4" />
              訂閱行事曆
            </Link>
          </div>

          <div
            class="flex w-full flex-col-reverse gap-2 sm:flex-row lg:w-auto bottom-nav:flex-row"
          >
            <form
              method="GET"
              :action="`/schedules/${viewModel.uuid}`"
              class="w-full sm:w-1/2 lg:w-32 bottom-nav:flex-1"
            >
              <label for="term" class="sr-only">選擇學期</label>
              <div class="relative">
                <select
                  id="term"
                  name="term"
                  aria-label="選擇學期"
                  data-offline-disable
                  class="h-10 w-full appearance-none rounded-lg border border-theme-200 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900"
                  :value="viewModel.selectedTerm"
                  @change="$event.target.form.submit()"
                >
                  <option
                    v-for="term in viewModel.availableTerms"
                    :key="term"
                    :value="term"
                  >
                    {{ toShortSemesterDisplay(term) }}
                  </option>
                </select>
                <div
                  class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                >
                  <Icon name="chevron-down" class="size-5 text-gray-400" />
                </div>
              </div>
            </form>

            <div
              v-if="push"
              v-show="push.supported.value"
              class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border border-theme-200 bg-white px-3 sm:w-1/2 lg:w-auto dark:border-zinc-700 dark:bg-zinc-900 bottom-nav:hidden"
            >
              <span
                class="text-sm font-medium text-theme-800 dark:text-zinc-200"
              >
                面授開始前接收桌面通知
              </span>
              <button
                type="button"
                role="switch"
                :aria-checked="push.enabled.value"
                aria-label="面授開始前接收桌面通知"
                :disabled="push.busy.value"
                :class="
                  push.enabled.value
                    ? 'bg-theme-700 dark:bg-zinc-300'
                    : 'bg-theme-200 dark:bg-zinc-700'
                "
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-60"
                data-analytics-event="schedule_push_toggle"
                data-analytics-feature="schedule"
                @click="push.toggle()"
              >
                <span
                  :class="
                    push.enabled.value ? 'translate-x-6' : 'translate-x-1'
                  "
                  class="inline-block size-4 transform rounded-full bg-white shadow transition-transform dark:bg-zinc-900"
                ></span>
              </button>
            </div>

            <div
              ref="actionsMenu"
              class="relative hidden bottom-nav:block"
              data-testid="schedule-actions"
            >
              <button
                type="button"
                data-testid="schedule-actions-toggle"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
                aria-haspopup="menu"
                :aria-expanded="actionsOpen.toString()"
                aria-controls="schedule-actions-menu"
                @click="actionsOpen = !actionsOpen"
              >
                <Icon name="ellipsis-horizontal" class="size-5" />
                更多
              </button>

              <div
                v-if="actionsOpen"
                id="schedule-actions-menu"
                data-testid="schedule-actions-menu"
                role="menu"
                class="absolute top-full right-0 z-30 mt-2 w-64 space-y-1 rounded-lg border border-theme-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
              >
                <Link
                  :href="`/schedules/${viewModel.uuid}/${viewModel.selectedTerm}/learning-progress`"
                  role="menuitem"
                  data-analytics-event="learning_progress_open"
                  data-analytics-feature="learning_progress"
                  class="flex items-center gap-3 rounded-md px-3 py-3 text-sm font-medium text-theme-800 transition-colors hover:bg-theme-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                  <Icon name="clipboard" class="size-5 shrink-0" />
                  學習進度表
                </Link>
                <Link
                  :href="`/schedules/${viewModel.uuid}/subscribe`"
                  role="menuitem"
                  data-analytics-event="calendar_subscribe_open"
                  data-analytics-feature="schedule"
                  class="flex items-center gap-3 rounded-md px-3 py-3 text-sm font-medium text-theme-800 transition-colors hover:bg-theme-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                  <Icon name="calendar" class="size-5 shrink-0" />
                  訂閱行事曆
                </Link>
                <Link
                  :href="`/schedules/${viewModel.uuid}/edit?term=${viewModel.selectedTerm}`"
                  role="menuitem"
                  data-analytics-event="schedule_edit"
                  data-analytics-feature="schedule"
                  class="flex items-center gap-3 rounded-md px-3 py-3 text-sm font-medium text-theme-800 transition-colors hover:bg-theme-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                  <Icon name="pencil-square" class="size-5 shrink-0" />
                  編輯
                </Link>
                <Link
                  :href="`/schedules/${viewModel.uuid}/customize`"
                  role="menuitem"
                  data-analytics-event="schedule_customize_open"
                  data-analytics-feature="schedule"
                  class="flex items-center gap-3 rounded-md px-3 py-3 text-sm font-medium text-theme-800 transition-colors hover:bg-theme-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                  <Icon name="cog-6-tooth" class="size-5 shrink-0" />
                  自訂
                </Link>

                <div
                  v-if="push && push.supported.value"
                  class="flex items-center justify-between gap-3 border-t border-theme-200 px-3 pt-3 pb-2 dark:border-zinc-700"
                >
                  <span
                    class="text-sm font-medium text-theme-800 dark:text-zinc-200"
                  >
                    面授開始前接收桌面通知
                  </span>
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="push.enabled.value"
                    aria-label="面授開始前接收桌面通知"
                    :disabled="push.busy.value"
                    :class="
                      push.enabled.value
                        ? 'bg-theme-700 dark:bg-zinc-300'
                        : 'bg-theme-200 dark:bg-zinc-700'
                    "
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-60"
                    data-analytics-event="schedule_push_toggle"
                    data-analytics-feature="schedule"
                    @click="push.toggle()"
                  >
                    <span
                      :class="
                        push.enabled.value ? 'translate-x-6' : 'translate-x-1'
                      "
                      class="inline-block size-4 transform rounded-full bg-white shadow transition-transform dark:bg-zinc-900"
                    ></span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <Greeting
        v-if="viewModel.displayOptions.show_greeting"
        class="mb-4"
        :semester-label="greeting.semesterLabel"
        :semester-code="greeting.semesterCode"
        :semester-start="greeting.semesterStart"
        :semester-end="greeting.semesterEnd"
      />

      <div
        v-if="!hasCourses"
        class="mb-8 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="mb-4">
          <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
            此學期尚無課程
          </h2>
        </div>
        <div class="space-y-3 text-theme-700 dark:text-zinc-300">
          <p>
            目前選擇的學期
            <span class="font-semibold text-theme-900 dark:text-zinc-100">
              {{ toSemesterDisplay(viewModel.selectedTerm) }}
            </span>
            沒有課程。
          </p>

          <p class="text-sm text-theme-700 dark:text-zinc-400">
            您可以切換其他學期，或前往
            <Link
              :href="`/schedules/${viewModel.uuid}/edit?term=${viewModel.selectedTerm}`"
              class="font-semibold text-theme-800 underline underline-offset-4 hover:text-theme-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100"
            >
              編輯課表
            </Link>
            新增課程。
          </p>
        </div>
      </div>

      <!-- Schedule Items - Responsive Table/Cards -->
      <div
        v-if="viewModel.displayOptions.show_schedule_items && hasCourses"
        class="mb-4 md:overflow-hidden md:rounded-lg md:border md:border-theme-200 md:bg-white dark:md:border-zinc-700 dark:md:bg-zinc-900"
      >
        <!-- 桌面版表格 -->
        <div class="hidden overflow-x-auto md:block">
          <table
            class="w-full border-collapse text-left text-theme-700 dark:text-zinc-300"
            aria-describedby="schedule-items-caption"
          >
            <caption id="schedule-items-caption" class="sr-only">
              課程時間表項目清單
            </caption>

            <thead
              class="border-b-2 border-theme-300 bg-theme-100 dark:border-zinc-600 dark:bg-zinc-900"
            >
              <tr>
                <th
                  scope="col"
                  class="px-4 py-3 font-bold text-theme-900 dark:text-zinc-100"
                >
                  課程名稱
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 font-bold text-theme-900 dark:text-zinc-100"
                >
                  班級
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 font-bold text-theme-900 dark:text-zinc-100"
                >
                  下次上課
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 font-bold text-theme-900 dark:text-zinc-100"
                >
                  時間
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 font-bold text-theme-900 dark:text-zinc-100"
                >
                  教師
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 font-bold text-theme-900 dark:text-zinc-100"
                >
                  <span class="sr-only">動作</span>
                </th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="row in itemRows"
                :key="row.i"
                class="border-b border-theme-200 hover:bg-theme-50 dark:border-zinc-700 dark:hover:bg-zinc-950"
              >
                <th
                  scope="row"
                  class="px-4 py-3 font-semibold text-theme-900 dark:text-zinc-100"
                >
                  {{ row.item.courseName }}
                </th>

                <td
                  class="px-4 py-3 text-sm text-theme-800 tabular-nums dark:text-zinc-200"
                >
                  <span
                    v-if="!row.item.isTentative"
                    class="inline-block rounded bg-theme-100 px-2 py-1 font-mono text-xs font-normal text-theme-800 dark:bg-zinc-800 dark:text-zinc-200"
                  >
                    <span v-if="row.item.code === 'ZZZ000'">統一面授</span>
                    <template v-else>
                      <span class="sr-only">班級代碼：</span>
                      <span>{{ row.item.code }}</span>
                    </template>
                  </span>
                  <span
                    v-else
                    class="ml-1 inline-block rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                  >
                    尚未分班
                  </span>
                </td>

                <td
                  class="px-4 py-3 text-theme-800 tabular-nums dark:text-zinc-200"
                >
                  <span v-if="row.next">{{ taipeiDate(row.next) }}</span>
                  <span v-else class="text-sm text-theme-700 dark:text-zinc-400"
                    >無未來課程</span
                  >
                </td>

                <td
                  class="px-4 py-3 text-theme-800 tabular-nums dark:text-zinc-200"
                >
                  <div v-if="row.next && taipeiTime(row.next)">
                    <span class="inline-flex items-center gap-1">
                      <span>{{ taipeiTime(row.next) }}</span>
                      <span v-if="row.next.hasOverride" class="inline-flex">
                        <Icon
                          name="exclamation-triangle"
                          class="size-4 text-theme-700 dark:text-zinc-400"
                          title="該次課程時間與一般時間不同"
                        />
                        <span class="sr-only"
                          >該次課程時間與一般時間不同。</span
                        >
                      </span>
                    </span>
                    <div
                      v-if="localHint(row.next)"
                      class="text-xs text-theme-700 dark:text-zinc-400"
                    >
                      {{ localHint(row.next) }}
                    </div>
                  </div>
                  <span
                    v-else-if="row.next"
                    class="text-sm text-theme-700 dark:text-zinc-500"
                    >未設定</span
                  >
                </td>

                <td class="px-4 py-3 text-theme-800 dark:text-zinc-200">
                  <span
                    v-if="teacher(row.item)"
                    class="inline-flex flex-wrap items-baseline gap-1"
                    :aria-label="row.item.teacherName"
                  >
                    <span v-show="teacher(row.item).base" class="shrink-0">{{
                      teacher(row.item).base
                    }}</span>
                    <span
                      v-show="teacher(row.item).suffix"
                      class="align-text-top text-xs"
                      >{{ teacher(row.item).suffix }}</span
                    >
                  </span>
                  <span v-else>−</span>
                </td>

                <td class="px-4 py-3 text-theme-800 dark:text-zinc-200">
                  <Link
                    :href="row.item.courseInfoUrl"
                    class="mr-3 inline-flex items-center gap-1 font-semibold text-theme-800 underline underline-offset-4 hover:text-theme-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100"
                    :aria-label="row.item.courseName + ' 的課程資訊'"
                  >
                    <Icon name="information-circle" class="inline size-4" />
                    課程資訊
                  </Link>

                  <a
                    v-show="row.item.videoLink"
                    :href="row.item.videoLink"
                    target="_blank"
                    rel="noopener"
                    data-offline-allow
                    class="inline-flex items-center gap-1 font-semibold text-theme-700 underline underline-offset-4 hover:text-theme-800 hover:no-underline dark:text-zinc-400 dark:hover:text-zinc-500"
                    :aria-label="
                      '前往 ' + row.item.courseName + ' 的視訊上課連結'
                    "
                  >
                    <Icon name="video-camera" class="inline size-4" />
                    視訊上課
                  </a>

                  <a
                    v-show="row.item.backupClassroomUrl"
                    :href="row.item.backupClassroomUrl"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1 font-semibold text-theme-700 underline underline-offset-4 hover:text-theme-900 hover:no-underline dark:text-zinc-300 dark:hover:text-zinc-100"
                    :aria-label="
                      '前往 ' + row.item.courseName + ' 的備用教室連結'
                    "
                  >
                    <Icon name="squares-plus" class="inline size-4" />
                    備用教室
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- 手機版卡片列表 -->
        <div class="space-y-3 md:hidden">
          <article
            v-for="row in itemRows"
            :key="row.i"
            data-testid="schedule-item-card"
            class="rounded-lg border border-theme-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <div class="flex items-center gap-3">
              <div
                class="flex w-20 shrink-0 flex-col items-center justify-center rounded-lg bg-theme-100 px-2 py-2 text-center dark:bg-zinc-800"
              >
                <template v-if="row.next">
                  <p
                    class="text-lg font-bold text-theme-700 dark:text-zinc-200"
                  >
                    {{ monthDay(row.next) }}
                  </p>
                  <p class="mt-0.5 text-base text-theme-700 dark:text-zinc-400">
                    {{ weekday(row.next) }}
                  </p>
                </template>
                <p
                  v-else
                  class="text-lg font-bold text-theme-700 dark:text-zinc-500"
                >
                  —
                </p>
              </div>

              <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                  <h3
                    class="line-clamp-2 min-w-0 flex-1 text-sm font-semibold text-theme-900 dark:text-zinc-100"
                  >
                    {{ row.item.courseName }}
                  </h3>

                  <span
                    v-if="row.next && isOngoing(row.next)"
                    class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300"
                  >
                    進行中
                  </span>
                </div>

                <div class="flex flex-wrap items-end justify-between gap-y-2">
                  <div class="flex shrink-0 flex-col gap-1">
                    <p
                      class="flex items-center gap-1.5 text-xs text-theme-700 dark:text-zinc-400"
                    >
                      <span
                        v-if="!row.item.isTentative"
                        class="inline-block rounded bg-theme-100 px-1.5 py-0.5 font-mono text-theme-800 dark:bg-zinc-800 dark:text-zinc-200"
                      >
                        <span v-if="row.item.code === 'ZZZ000'">統一面授</span>
                        <template v-else>
                          <span class="sr-only">班級代碼：</span>
                          <span>{{ row.item.code }}</span>
                        </template>
                      </span>
                      <span
                        v-else
                        class="inline-block rounded bg-amber-100 px-1.5 py-0.5 font-semibold text-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                      >
                        尚未分班
                      </span>

                      <span v-if="teacher(row.item)">
                        {{ teacher(row.item).base }}
                        <small v-if="teacher(row.item).suffix">{{
                          teacher(row.item).suffix
                        }}</small>
                      </span>
                    </p>

                    <div v-if="row.next">
                      <p
                        v-if="taipeiTime(row.next)"
                        class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-theme-800 tabular-nums dark:text-zinc-200"
                      >
                        {{ taipeiTime(row.next) }}
                        <Icon
                          v-if="row.next.hasOverride"
                          name="exclamation-triangle"
                          class="size-4 text-theme-700 dark:text-zinc-400"
                          title="該次課程時間與一般時間不同"
                        />
                      </p>
                      <p
                        v-else
                        class="mt-1 text-sm text-theme-700 dark:text-zinc-500"
                      >
                        時間未設定
                      </p>
                      <p
                        v-if="localHint(row.next)"
                        class="text-xs text-theme-700 dark:text-zinc-400"
                      >
                        {{ localHint(row.next) }}
                      </p>
                    </div>
                    <p
                      v-else
                      class="mt-1 text-sm font-medium text-theme-700 dark:text-zinc-400"
                    >
                      無未來課程
                    </p>
                  </div>

                  <div class="flex shrink-0 flex-col items-end gap-1">
                    <a
                      v-show="row.item.videoLink"
                      :href="row.item.videoLink"
                      target="_blank"
                      rel="noopener"
                      data-offline-allow
                      class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-theme-300 px-3 py-1.5 text-base font-medium text-theme-700 transition hover:bg-theme-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                      :aria-label="
                        '前往 ' + row.item.courseName + ' 的視訊上課連結'
                      "
                    >
                      <Icon name="video-camera" class="size-5" />
                      進入教室
                    </a>

                    <div class="flex items-center">
                      <a
                        v-show="row.item.backupClassroomUrl"
                        :href="row.item.backupClassroomUrl"
                        target="_blank"
                        rel="noopener"
                        title="主教室人數已滿時可改用此備用連結"
                        class="rounded-lg px-2 py-1 text-xs font-medium text-theme-700 transition hover:text-theme-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                        :aria-label="
                          '前往 ' + row.item.courseName + ' 的備用教室連結'
                        "
                      >
                        備用教室
                      </a>
                      <Link
                        :href="row.item.courseInfoUrl"
                        class="rounded-lg px-2 py-1 text-xs font-medium text-theme-700 transition hover:text-theme-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                        :aria-label="row.item.courseName + ' 的課程資訊'"
                      >
                        課程資訊
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </article>
        </div>

        <div
          v-if="viewModel.hasAnyOverride"
          class="mt-3 flex items-center gap-1 px-1 text-xs text-theme-700 md:mt-0 md:border-t md:border-theme-200 md:bg-theme-50 md:px-4 md:py-2 dark:text-zinc-400 dark:md:border-zinc-700 dark:md:bg-zinc-950"
        >
          <Icon
            name="exclamation-triangle"
            class="size-4 text-theme-700 dark:text-zinc-400"
          />
          <span>表示該次課程時間與一般時間不同</span>
        </div>
      </div>

      <div
        v-if="hasTentative"
        class="mb-8 flex items-start justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
        role="status"
      >
        <div class="flex items-start gap-3">
          <Icon name="exclamation-triangle" class="mt-0.5 size-5 shrink-0" />
          <p>
            尚有未選擇班級的課程，開學分班後記得回來選擇班級，才能看到視訊面授連結喔！
          </p>
        </div>
      </div>

      <div
        v-if="hasPending"
        class="mb-8 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="mb-4">
          <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
            尚有未選擇班級的課程
          </h2>
        </div>
        <ul class="space-y-2">
          <li
            v-for="item in pendingItems"
            :key="item.id"
            class="flex items-center justify-between gap-3 text-theme-700 dark:text-zinc-300"
          >
            <span>{{ item.courseName }}</span>
            <Link
              :href="`/schedules/${viewModel.uuid}/edit?term=${viewModel.selectedTerm}`"
              class="shrink-0 font-semibold text-theme-800 underline underline-offset-4 hover:text-theme-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100"
            >
              前往選擇班級
            </Link>
          </li>
        </ul>
      </div>

      <CommonLinks
        v-if="viewModel.displayOptions.show_common_links"
        class="mb-8"
        :custom-links="viewModel.customLinks"
      />

      <!-- Schedule Calendar View -->
      <div
        v-if="
          viewModel.displayOptions.show_class_dates &&
          viewModel.months.length > 0
        "
        class="mb-8"
      >
        <ClassDates
          :months="viewModel.months"
          :has-any-override="viewModel.hasAnyOverride"
        />
      </div>

      <!-- School Calendar -->
      <SchoolCalendar
        v-if="viewModel.displayOptions.show_school_calendar"
        class="mb-8"
        :events="schoolCalendar.events"
        :show-past-events="schoolCalendar.showPastEvents"
      />

      <div
        v-if="viewModel.displayOptions.show_exam_info"
        class="mb-8 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="mb-4">
          <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
            考試資訊
          </h2>
          <div class="text-sm text-theme-700 dark:text-zinc-400">
            以下為您加入課表的科目之期中 / 期末考試日期與節次。
          </div>
        </div>

        <!-- 手機：卡片列表 -->
        <div class="space-y-3 md:hidden">
          <div
            v-for="exam in viewModel.exams"
            :key="exam.courseId"
            class="rounded-lg border border-theme-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1">
                <div class="font-semibold text-theme-900 dark:text-zinc-100">
                  {{ exam.courseName }}
                </div>
                <div class="mt-1 flex items-center gap-2">
                  <ClassCode v-if="exam.classCode" :code="exam.classCode" />

                  <Link
                    :href="`/courses/${exam.courseId}#previous-exams`"
                    class="mr-3 inline-flex items-center gap-1 text-sm font-semibold text-theme-800 underline underline-offset-4 hover:text-theme-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100"
                    :aria-label="exam.courseName + ' 的課程資訊'"
                  >
                    <Icon name="information-circle" class="inline size-4" />
                    考古題
                  </Link>
                </div>
              </div>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-3">
              <div v-if="!viewModel.selectedTerm.endsWith('C')">
                <p
                  class="mb-1 text-xs font-semibold tracking-wide text-theme-700 uppercase dark:text-zinc-400"
                >
                  期中考
                </p>
                <template v-if="exam.midtermDate">
                  <div class="font-semibold text-theme-900 dark:text-zinc-100">
                    {{ exam.formattedMidtermDate }}
                  </div>
                  <div
                    v-if="exam.formattedExamTime"
                    class="mt-1 text-sm text-theme-700 dark:text-zinc-400"
                  >
                    {{ exam.formattedExamTime }}
                  </div>
                </template>
                <div v-else class="text-theme-700 dark:text-zinc-400">—</div>
              </div>

              <div>
                <p
                  class="mb-1 text-xs font-semibold tracking-wide text-theme-700 uppercase dark:text-zinc-400"
                >
                  期末考
                </p>
                <template v-if="exam.finalDate">
                  <div class="font-semibold text-theme-900 dark:text-zinc-100">
                    {{ exam.formattedFinalDate }}
                  </div>
                  <div
                    v-if="exam.formattedExamTime"
                    class="mt-1 text-sm text-theme-700 dark:text-zinc-400"
                  >
                    {{ exam.formattedExamTime }}
                  </div>
                </template>
                <div v-else class="text-theme-700 dark:text-zinc-400">—</div>
              </div>
            </div>
          </div>

          <div
            v-if="viewModel.exams.length === 0"
            class="px-4 py-16 text-center text-theme-700 dark:text-zinc-400"
          >
            您的課表中沒有任何科目有設定考試日期。
          </div>
        </div>

        <!-- 桌面：維持表格，但只在 md+ 顯示 -->
        <div class="hidden overflow-x-auto md:block">
          <table
            class="w-full border-collapse overflow-hidden rounded text-left text-theme-700 dark:text-zinc-300"
          >
            <thead>
              <tr
                class="rounded-t border-b-2 border-theme-300 bg-theme-100 dark:border-zinc-600 dark:bg-zinc-900"
              >
                <th
                  class="px-4 py-3 font-bold text-theme-900 dark:text-zinc-100"
                >
                  課程
                </th>
                <th
                  v-if="!viewModel.selectedTerm.endsWith('C')"
                  class="px-4 py-3 font-bold text-theme-900 dark:text-zinc-100"
                >
                  期中考
                </th>
                <th
                  class="px-4 py-3 font-bold text-theme-900 dark:text-zinc-100"
                >
                  期末考
                </th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="exam in viewModel.exams"
                :key="exam.courseId"
                class="border-b border-theme-200 hover:bg-theme-50 dark:border-zinc-700 dark:hover:bg-zinc-950"
              >
                <td
                  class="px-4 py-3 font-semibold text-theme-900 dark:text-zinc-100"
                >
                  {{ exam.courseName }}
                  <div class="mt-1 flex items-center gap-2">
                    <ClassCode v-if="exam.isTentative" code="尚未分班" />
                    <ClassCode
                      v-else-if="exam.classCode"
                      :code="exam.classCode"
                    />

                    <Link
                      :href="`/courses/${exam.courseId}#previous-exams`"
                      class="mr-3 inline-flex items-center gap-1 text-sm font-semibold text-theme-800 underline underline-offset-4 hover:text-theme-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100"
                      :aria-label="exam.courseName + ' 的課程資訊'"
                    >
                      <Icon name="information-circle" class="inline size-4" />
                      考古題
                    </Link>
                  </div>
                </td>

                <td
                  v-if="!viewModel.selectedTerm.endsWith('C')"
                  class="px-4 py-3 tabular-nums"
                >
                  <div v-if="exam.midtermDate" class="font-semibold">
                    {{ exam.formattedMidtermDate }}
                  </div>
                  <div v-else class="text-theme-700 dark:text-zinc-400">—</div>
                  <div
                    v-if="exam.formattedExamTime"
                    class="mt-1 text-sm text-theme-700 dark:text-zinc-400"
                  >
                    {{ exam.formattedExamTime }}
                  </div>
                </td>

                <td class="px-4 py-3 tabular-nums">
                  <div v-if="exam.finalDate" class="font-semibold">
                    {{ exam.formattedFinalDate }}
                  </div>
                  <div v-else class="text-theme-700 dark:text-zinc-400">—</div>
                  <div
                    v-if="exam.formattedExamTime"
                    class="mt-1 text-sm text-theme-700 dark:text-zinc-400"
                  >
                    {{ exam.formattedExamTime }}
                  </div>
                </td>
              </tr>

              <tr v-if="viewModel.exams.length === 0">
                <td
                  colspan="3"
                  class="px-4 py-16 text-center text-theme-700 dark:text-zinc-400"
                >
                  您的課表中沒有任何科目有設定考試日期。
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Announcements -->
      <div v-if="viewModel.displayOptions.show_announcements" v-show="!offline">
        <AnnouncementsWidget
          class="mb-8"
          :schedule-uuid="viewModel.uuid"
          :has-any-selection="announcementsWidget.hasAnySelection"
          :announcements="announcementsWidget.announcements"
          :more-announcements-url="announcementsWidget.moreAnnouncementsUrl"
        />
      </div>

      <!-- Share Section -->
      <div
        v-if="viewModel.displayOptions.show_share_section"
        class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="flex items-center justify-between gap-4">
          <div class="w-full md:w-auto md:flex-1">
            <p class="mb-3 text-theme-700 dark:text-zinc-300">
              您可以使用以下連結來編輯或檢視此課表，請妥善保管此連結。
              <br />
              <span
                class="inline-flex items-center gap-1 font-semibold text-red-600"
              >
                <Icon name="exclamation-triangle" class="size-4" />
                注意：任何擁有此連結的人都可以編輯您的課表。
              </span>
            </p>

            <div
              class="rounded border border-theme-300 bg-white text-sm text-theme-700 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-400"
            >
              <div class="flex items-stretch gap-3">
                <input
                  ref="shareInput"
                  class="flex-1 px-3 py-2 font-mono break-all text-theme-700 dark:text-zinc-400"
                  :value="copyShareUrl"
                  readonly
                  aria-label="我的課表連結"
                  @click="$event.target.select()"
                />

                <div class="shrink-0">
                  <button
                    type="button"
                    :aria-pressed="copied.toString()"
                    class="ml-2 h-full rounded-l-none rounded-r border border-theme-200 bg-theme-200 px-3 py-1 text-sm font-semibold whitespace-nowrap text-theme-900 transition hover:bg-theme-300 dark:border-zinc-700 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600"
                    @click="copy()"
                  >
                    <span v-show="!copied">
                      <Icon name="clipboard-document" class="inline size-4" />
                      複製連結
                    </span>
                    <span v-show="copied">
                      <Icon name="check" class="inline size-4" />
                      已複製！
                    </span>
                  </button>

                  <div class="sr-only" role="status" aria-live="polite">
                    {{ copied ? '已複製' : '' }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="hidden w-28 flex-col items-center justify-center md:flex">
            <div
              class="rounded border border-theme-200 bg-white p-2"
              v-html="qrCodeSvg"
            ></div>
          </div>
        </div>
      </div>

      <div
        v-if="viewModel.displayOptions.show_print_button"
        class="mt-6 flex justify-end"
      >
        <div
          ref="printMenu"
          class="relative flex flex-col items-end gap-2"
          data-testid="schedule-print"
        >
          <button
            type="button"
            data-testid="schedule-print-button"
            :disabled="pdfShare.state.value === 'loading'"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-200 bg-theme-200 px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-300 disabled:cursor-wait disabled:opacity-70 dark:border-zinc-700 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600"
            aria-haspopup="menu"
            :aria-expanded="printOpen.toString()"
            aria-controls="schedule-print-menu"
            @click="onPrintButton"
          >
            <template v-if="pdfShare.state.value === 'loading'">
              <Icon name="arrow-path" class="size-4 animate-spin" />
              產生 PDF 中…
            </template>
            <template v-else-if="pdfShare.state.value === 'ready'">
              <Icon name="share" class="size-4" />
              分享 PDF
            </template>
            <template v-else>
              <Icon name="printer" class="inline size-4" />
              列印
              <Icon name="chevron-down" class="size-4" />
            </template>
          </button>

          <p
            v-if="pdfShare.state.value === 'failed'"
            role="alert"
            data-testid="schedule-print-error"
            class="text-sm text-red-600"
          >
            無法產生 PDF，請稍後再試一次。
          </p>

          <div class="sr-only" role="status" aria-live="polite">
            {{ pdfShare.state.value === 'loading' ? '正在產生 PDF' : '' }}
          </div>

          <div
            v-if="printOpen"
            id="schedule-print-menu"
            data-testid="schedule-print-menu"
            role="menu"
            class="absolute right-0 bottom-full z-30 mb-2 w-48 rounded-lg border border-theme-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
          >
            <p class="px-3 pt-1 pb-2 text-xs text-theme-700 dark:text-zinc-400">
              選擇月曆版本
            </p>
            <!-- The PDF is rendered on the server (Blade + Browsershot), so
            these are plain links, not Inertia visits. -->
            <a
              v-for="option in printOptions"
              :key="option.weekStart"
              :href="printUrl(option)"
              target="_blank"
              rel="noopener"
              role="menuitem"
              :data-testid="`schedule-print-${option.weekStart}`"
              class="flex items-center gap-3 rounded-md px-3 py-3 text-sm font-medium text-theme-800 transition-colors hover:bg-theme-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
              @click="onPrintOption($event, option)"
            >
              {{ option.label }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
