<script setup>
// Vue port of resources/views/schedule/show.blade.php. The page's own small
// widgets (remember-schedule modal, push notification toggle, "Add to
// bookmarks" hint, copy-link box) use the already-ported Phase 1
// composables (usePwaStandalone, usePushSubscription, useCopyLink). The
// item table (previously the `nouToolsScheduleItems` Alpine component,
// resources/js/schedule-items.js) is only used on this page, so its
// row-sorting/"next class" logic is ported inline here, mirroring how
// Courses/Schedule.vue inlines its one-off `courseSchedule` logic.
//
// NOTE: the Blade view rendered a schedule-scoped PWA manifest link
// (`:pwaScheduleUuid="$viewModel->uuid"`, see resources/views/components/
// layout.blade.php) so "Add to Home Screen" installs a schedule-specific
// shortcut. Inertia's root template (resources/views/app.blade.php) renders
// one static site-wide manifest link that isn't easily overridden per-page
// without risking a duplicate/conflicting <link> tag, so that scoping is
// not carried over here — installs fall back to the site-wide manifest.
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import Greeting from '../../Components/Greeting.vue'
import CommonLinks from '../../Components/CommonLinks.vue'
import SchoolCalendar from '../../Components/SchoolCalendar.vue'
import AnnouncementsWidget from '../../Components/AnnouncementsWidget.vue'
import ClassCode from '../../Components/ClassCode.vue'
import PwaInstallBanner from '../../Components/PwaInstallBanner.vue'
import usePwaStandalone from '../../Composables/usePwaStandalone'
import usePushSubscription from '../../Composables/usePushSubscription'
import useCopyLink from '../../Composables/useCopyLink'

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
    })
  : null

// --- copy share link ---
const shareInput = ref(null)
const {
  shareUrl: copyShareUrl,
  copied,
  copy,
} = useCopyLink({ shareUrl: props.shareUrl }, shareInput)

function print() {
  window.print()
}

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
  <Head :title="pageTitle">
    <meta name="robots" content="noindex, nofollow" />
  </Head>

  <AppLayout>
    <div class="mx-auto max-w-5xl">
      <div
        v-show="offline"
        class="mb-6 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200 print:hidden"
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
              class="mb-2 text-lg font-semibold text-warm-900 dark:text-zinc-100"
            >
              要記住這個課表嗎？
            </h3>
            <p class="mb-4 text-sm text-warm-600 dark:text-zinc-400">
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
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-warm-500 bg-white px-4 py-2 font-semibold text-warm-900 transition hover:bg-warm-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
                @click="showRememberModal = false"
              >
                不用了
              </button>
              <button
                type="submit"
                data-testid="remember-schedule-confirm"
                data-analytics-event="remember_schedule_confirm"
                data-analytics-feature="schedule"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-warm-600 bg-warm-600 px-4 py-2 font-semibold text-white transition hover:bg-warm-700"
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
          <h2 class="mb-2 text-3xl font-bold text-warm-900 dark:text-zinc-100">
            {{ viewModel.name || '我的課表' }}
          </h2>
          <p
            v-show="!isPwa"
            class="mt-1 flex items-center gap-1 text-sm text-warm-600 dark:text-zinc-400 print:hidden"
          >
            <Icon name="information-circle" class="inline size-4" />
            小提示：將此頁加入瀏覽器書籤，下次即可快速開啟課表。
          </p>
        </div>

        <div class="flex w-full flex-col items-end gap-2 lg:w-auto">
          <div
            class="flex w-full flex-col-reverse gap-2 sm:flex-row lg:w-auto print:hidden"
          >
            <div class="flex w-full shrink-0 gap-2 sm:w-1/2 lg:w-auto">
              <Link
                :href="`/schedules/${viewModel.uuid}/edit?term=${viewModel.selectedTerm}`"
                data-analytics-event="schedule_edit"
                data-analytics-feature="schedule"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-warm-500 bg-white px-4 py-2 font-semibold text-warm-900 transition hover:bg-warm-50 sm:w-1/2 lg:w-auto dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
              >
                <Icon name="pencil-square" class="size-4" />
                編輯
              </Link>

              <Link
                :href="`/schedules/${viewModel.uuid}/customize`"
                data-analytics-event="schedule_customize_open"
                data-analytics-feature="schedule"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-warm-500 bg-white px-4 py-2 font-semibold text-warm-900 transition hover:bg-warm-50 sm:w-1/2 lg:w-auto dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
              >
                <Icon name="cog-6-tooth" class="size-4" />
                自訂
              </Link>
            </div>

            <Link
              :href="`/schedules/${viewModel.uuid}/${viewModel.selectedTerm}/learning-progress`"
              data-analytics-event="learning_progress_open"
              data-analytics-feature="learning_progress"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-warm-500 bg-white px-4 py-2 font-semibold text-warm-900 transition hover:bg-warm-50 sm:w-1/2 lg:w-auto dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
            >
              <Icon name="clipboard" class="size-4" />
              學習進度表
            </Link>

            <Link
              :href="`/schedules/${viewModel.uuid}/subscribe`"
              data-analytics-event="calendar_subscribe_open"
              data-analytics-feature="schedule"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-orange-500 bg-orange-500 px-4 py-2 font-semibold text-white transition hover:bg-orange-600 sm:w-1/2 lg:w-auto"
            >
              <Icon name="calendar" class="inline size-4" />
              訂閱行事曆
            </Link>
          </div>

          <div
            class="flex w-full flex-col-reverse gap-2 sm:flex-row lg:w-auto print:hidden"
          >
            <form
              method="GET"
              :action="`/schedules/${viewModel.uuid}`"
              class="w-full sm:w-1/2 lg:w-32"
            >
              <label for="term" class="sr-only">選擇學期</label>
              <select
                id="term"
                name="term"
                aria-label="選擇學期"
                data-offline-disable
                class="h-10 w-full appearance-none rounded-lg border border-warm-200 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900"
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
            </form>

            <div
              v-if="push"
              v-show="push.supported.value"
              class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border border-warm-200 bg-white px-3 sm:w-1/2 lg:w-auto dark:border-zinc-700 dark:bg-zinc-900"
            >
              <span
                class="text-sm font-medium text-warm-800 dark:text-zinc-200"
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
                    ? 'bg-warm-700 dark:bg-zinc-300'
                    : 'bg-warm-200 dark:bg-zinc-700'
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

          <span
            class="hidden text-sm text-warm-600 dark:text-zinc-400 print:inline"
          >
            {{ toSemesterDisplay(viewModel.selectedTerm) }}
          </span>
        </div>
      </div>

      <Greeting
        v-if="viewModel.displayOptions.show_greeting"
        class="mb-4 print:hidden"
        :semester-label="greeting.semesterLabel"
        :semester-code="greeting.semesterCode"
        :semester-start="greeting.semesterStart"
        :semester-end="greeting.semesterEnd"
      />

      <div
        v-if="!hasCourses"
        class="mb-8 rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="mb-4">
          <h2 class="text-xl font-semibold text-warm-900 dark:text-zinc-100">
            此學期尚無課程
          </h2>
        </div>
        <div class="space-y-3 text-warm-700 dark:text-zinc-300">
          <p>
            目前選擇的學期
            <span class="font-semibold text-warm-900 dark:text-zinc-100">
              {{ toSemesterDisplay(viewModel.selectedTerm) }}
            </span>
            沒有課程。
          </p>

          <p class="text-sm text-warm-600 dark:text-zinc-400">
            您可以切換其他學期，或前往
            <Link
              :href="`/schedules/${viewModel.uuid}/edit?term=${viewModel.selectedTerm}`"
              class="font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100"
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
        class="mb-4 overflow-hidden rounded-lg border border-warm-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
      >
        <!-- 桌面版表格 -->
        <div class="hidden overflow-x-auto md:block print:block">
          <table
            class="w-full border-collapse text-left text-warm-700 dark:text-zinc-300"
            aria-describedby="schedule-items-caption"
          >
            <caption id="schedule-items-caption" class="sr-only">
              課程時間表項目清單
            </caption>

            <thead
              class="border-b-2 border-warm-300 bg-warm-100 dark:border-zinc-600 dark:bg-zinc-900"
            >
              <tr>
                <th
                  scope="col"
                  class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100"
                >
                  課程名稱
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100"
                >
                  班級
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100 print:hidden"
                >
                  下次上課
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100 print:hidden"
                >
                  時間
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100"
                >
                  教師
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100 print:hidden"
                >
                  <span class="sr-only">動作</span>
                </th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="row in itemRows"
                :key="row.i"
                class="border-b border-warm-200 hover:bg-warm-50 dark:border-zinc-700 dark:hover:bg-zinc-950"
              >
                <th
                  scope="row"
                  class="px-4 py-3 font-semibold text-warm-900 dark:text-zinc-100"
                >
                  {{ row.item.courseName }}
                </th>

                <td
                  class="px-4 py-3 text-sm text-warm-800 tabular-nums dark:text-zinc-200"
                >
                  <span
                    v-if="!row.item.isTentative"
                    class="inline-block rounded bg-warm-100 px-2 py-1 font-mono text-xs font-normal text-warm-800 dark:bg-zinc-800 dark:text-zinc-200 print:bg-transparent print:p-0"
                  >
                    <span v-if="row.item.code === 'ZZZ000'">統一面授</span>
                    <template v-else>
                      <span class="sr-only">班級代碼：</span>
                      <span>{{ row.item.code }}</span>
                    </template>
                  </span>
                  <span
                    v-else
                    class="ml-1 inline-block rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/40 dark:text-amber-200 print:bg-transparent print:p-0"
                  >
                    尚未分班
                  </span>
                </td>

                <td
                  class="px-4 py-3 text-warm-800 tabular-nums dark:text-zinc-200 print:hidden"
                >
                  <span v-if="row.next">{{ taipeiDate(row.next) }}</span>
                  <span v-else class="text-sm text-warm-500 dark:text-zinc-400"
                    >無未來課程</span
                  >
                </td>

                <td
                  class="px-4 py-3 text-warm-800 tabular-nums dark:text-zinc-200 print:hidden"
                >
                  <div v-if="row.next && taipeiTime(row.next)">
                    <span class="inline-flex items-center gap-1">
                      <span>{{ taipeiTime(row.next) }}</span>
                      <span v-if="row.next.hasOverride" class="inline-flex">
                        <Icon
                          name="exclamation-triangle"
                          class="size-4 text-warm-500 dark:text-zinc-400"
                          title="該次課程時間與一般時間不同"
                        />
                        <span class="sr-only"
                          >該次課程時間與一般時間不同。</span
                        >
                      </span>
                    </span>
                    <div
                      v-if="localHint(row.next)"
                      class="text-xs text-warm-500 dark:text-zinc-400"
                    >
                      {{ localHint(row.next) }}
                    </div>
                  </div>
                  <span
                    v-else-if="row.next"
                    class="text-sm text-warm-400 dark:text-zinc-500"
                    >未設定</span
                  >
                </td>

                <td class="px-4 py-3 text-warm-800 dark:text-zinc-200">
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

                <td
                  class="px-4 py-3 text-warm-800 dark:text-zinc-200 print:hidden"
                >
                  <Link
                    :href="row.item.courseInfoUrl"
                    class="mr-3 inline-flex items-center gap-1 font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100"
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
                    class="inline-flex items-center gap-1 font-semibold text-warm-500 underline underline-offset-4 hover:text-warm-400 hover:no-underline dark:text-zinc-400 dark:hover:text-zinc-500"
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
                    class="inline-flex items-center gap-1 font-semibold text-warm-700 underline underline-offset-4 hover:text-warm-900 hover:no-underline dark:text-zinc-300 dark:hover:text-zinc-100"
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
        <div class="md:hidden print:hidden">
          <div
            v-for="row in itemRows"
            :key="row.i"
            class="border-b border-warm-200 last:border-b-0 dark:border-zinc-700"
          >
            <div
              class="m-0 border-0 border-b border-warm-200 bg-white p-4 transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900"
            >
              <h3
                class="mb-2 text-lg font-semibold text-warm-900 dark:text-zinc-100"
              >
                {{ row.item.courseName }}
              </h3>

              <div class="mb-3 flex items-center gap-2">
                <span
                  v-if="!row.item.isTentative"
                  class="inline-block rounded bg-warm-100 px-2 py-1 font-mono text-xs font-normal text-warm-800 dark:bg-zinc-800 dark:text-zinc-200"
                >
                  <span v-if="row.item.code === 'ZZZ000'">統一面授</span>
                  <template v-else>
                    <span class="sr-only">班級代碼：</span>
                    <span>{{ row.item.code }}</span>
                  </template>
                </span>
                <span
                  v-else
                  class="inline-block rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                >
                  尚未分班
                </span>

                <p
                  v-if="teacher(row.item)"
                  class="inline-flex items-baseline gap-1 text-warm-900 dark:text-zinc-100"
                >
                  <span v-show="teacher(row.item).base" class="text-sm">{{
                    teacher(row.item).base
                  }}</span>
                  <span
                    v-show="teacher(row.item).suffix"
                    class="text-xs text-warm-700 dark:text-zinc-300"
                    >{{ teacher(row.item).suffix }}</span
                  >
                </p>
              </div>

              <div class="mb-4 space-y-3">
                <div>
                  <p
                    class="mb-1 text-xs font-semibold tracking-wide text-warm-600 uppercase dark:text-zinc-400"
                  >
                    下次上課
                  </p>

                  <div v-if="row.next">
                    <p
                      class="inline-flex items-center gap-1 font-semibold text-warm-900 dark:text-zinc-100"
                    >
                      <span>{{ taipeiDate(row.next) }}</span>
                      <span
                        v-if="taipeiTime(row.next)"
                        class="inline-flex items-center gap-1"
                      >
                        <span>{{ taipeiTime(row.next) }}</span>
                        <Icon
                          v-if="row.next.hasOverride"
                          name="exclamation-triangle"
                          class="size-4 text-warm-500 dark:text-zinc-400"
                          title="該次課程時間與一般時間不同"
                        />
                      </span>
                    </p>
                    <p
                      v-if="localHint(row.next)"
                      class="text-xs text-warm-500 dark:text-zinc-400"
                    >
                      {{ localHint(row.next) }}
                    </p>
                  </div>
                  <p
                    v-else
                    class="font-semibold text-warm-500 dark:text-zinc-400"
                  >
                    無未來課程
                  </p>
                </div>
              </div>

              <div
                class="flex gap-2 border-t border-warm-100 pt-3 dark:border-zinc-800"
              >
                <Link
                  :href="row.item.courseInfoUrl"
                  class="flex-1 rounded px-2 py-2 text-center text-sm font-semibold text-warm-800 underline underline-offset-4 transition hover:bg-warm-50 hover:text-warm-900 dark:text-zinc-200 dark:hover:bg-zinc-950 dark:hover:text-zinc-100"
                >
                  <Icon name="information-circle" class="mr-1 inline size-4" />
                  課程資訊
                </Link>

                <a
                  v-show="row.item.videoLink"
                  :href="row.item.videoLink"
                  target="_blank"
                  rel="noopener"
                  data-offline-allow
                  class="flex-1 rounded px-2 py-2 text-center text-sm font-semibold text-warm-500 underline underline-offset-4 transition hover:bg-orange-50 hover:text-warm-400 dark:text-zinc-400 dark:hover:text-zinc-500"
                >
                  <Icon name="video-camera" class="mr-1 inline size-4" />
                  視訊上課
                </a>

                <a
                  v-show="row.item.backupClassroomUrl"
                  :href="row.item.backupClassroomUrl"
                  target="_blank"
                  rel="noopener"
                  class="flex-1 rounded px-2 py-2 text-center text-sm font-semibold text-warm-600 underline underline-offset-4 transition hover:bg-warm-50 hover:text-warm-500 dark:text-zinc-400 dark:hover:bg-zinc-950 dark:hover:text-zinc-400"
                >
                  <Icon name="squares-plus" class="mr-1 inline size-4" />
                  備用教室
                </a>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="viewModel.hasAnyOverride"
          class="flex items-center gap-1 border-t border-warm-200 bg-warm-50 px-4 py-2 text-xs text-warm-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-400"
        >
          <Icon
            name="exclamation-triangle"
            class="size-4 text-warm-500 dark:text-zinc-400"
          />
          <span>表示該次課程時間與一般時間不同</span>
        </div>
      </div>

      <div
        v-if="hasTentative"
        class="mb-8 flex items-start justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200 print:hidden"
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
        class="mb-8 rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="mb-4">
          <h2 class="text-xl font-semibold text-warm-900 dark:text-zinc-100">
            尚有未選擇班級的課程
          </h2>
        </div>
        <ul class="space-y-2">
          <li
            v-for="item in pendingItems"
            :key="item.id"
            class="flex items-center justify-between gap-3 text-warm-700 dark:text-zinc-300"
          >
            <span>{{ item.courseName }}</span>
            <Link
              :href="`/schedules/${viewModel.uuid}/edit?term=${viewModel.selectedTerm}`"
              class="shrink-0 font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100"
            >
              前往選擇班級
            </Link>
          </li>
        </ul>
      </div>

      <CommonLinks
        v-if="viewModel.displayOptions.show_common_links"
        class="mb-8 print:hidden"
        :custom-links="viewModel.customLinks"
      />

      <!-- Schedule Calendar View -->
      <div
        v-if="
          viewModel.displayOptions.show_class_dates &&
          viewModel.items.length > 0
        "
        class="mb-8"
      >
        <h3 class="mb-4 text-2xl font-bold text-warm-900 dark:text-zinc-100">
          面授日期
        </h3>
        <p
          v-if="viewModel.hasAnyOverride"
          class="mb-4 flex items-center gap-1 text-sm text-warm-600 dark:text-zinc-400"
        >
          <Icon name="exclamation-triangle" class="size-4 text-orange-600" />
          表示該次面授時間與一般時間不同
        </p>

        <div
          class="mb-4 grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2 print:grid-cols-1"
        >
          <div
            v-for="month in viewModel.months"
            :key="month.monthKey"
            class="rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <div class="mb-4">
              <h2
                class="text-xl font-semibold text-warm-900 dark:text-zinc-100"
              >
                {{ month.monthDisplay }}
              </h2>
            </div>
            <div
              class="grid grid-cols-1 space-y-3 gap-x-6 gap-y-1 print:grid-cols-2"
            >
              <div
                v-for="date in month.dates"
                :key="date.dateKey"
                class="break-inside-avoid-page border-l-4 border-warm-500 py-2 pl-4"
              >
                <div
                  class="mb-1 font-semibold text-warm-900 dark:text-zinc-100"
                >
                  {{ date.formattedDate }}
                </div>
                <div class="space-y-1">
                  <div
                    v-for="(course, i) in date.courses"
                    :key="i"
                    class="text-sm text-warm-700 dark:text-zinc-300"
                  >
                    <span class="font-semibold">{{ course.courseName }}</span>
                    <ClassCode
                      :code="course.isTentative ? '尚未分班' : course.code"
                    />
                    <br />
                    <span
                      class="inline-flex items-center gap-1 text-warm-600 dark:text-zinc-400"
                    >
                      {{ course.time }}
                      <Icon
                        v-if="course.hasOverride"
                        name="exclamation-triangle"
                        class="size-4 text-warm-500 dark:text-zinc-400"
                        title="該次課程時間與一般時間不同"
                      />
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
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
        class="mb-8 rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="mb-4">
          <h2 class="text-xl font-semibold text-warm-900 dark:text-zinc-100">
            考試資訊
          </h2>
          <div class="text-sm text-warm-600 dark:text-zinc-400">
            以下為您加入課表的科目之期中 / 期末考試日期與節次。
          </div>
        </div>

        <!-- 手機：卡片列表 -->
        <div class="space-y-3 md:hidden">
          <div
            v-for="exam in viewModel.exams"
            :key="exam.courseId"
            class="rounded-lg border border-warm-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1">
                <div class="font-semibold text-warm-900 dark:text-zinc-100">
                  {{ exam.courseName }}
                </div>
                <div class="mt-1 flex items-center gap-2">
                  <ClassCode v-if="exam.classCode" :code="exam.classCode" />

                  <Link
                    :href="`/courses/${exam.courseId}#previous-exams`"
                    class="mr-3 inline-flex items-center gap-1 text-sm font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100 print:hidden"
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
                  class="mb-1 text-xs font-semibold tracking-wide text-warm-600 uppercase dark:text-zinc-400"
                >
                  期中考
                </p>
                <template v-if="exam.midtermDate">
                  <div class="font-semibold text-warm-900 dark:text-zinc-100">
                    {{ exam.formattedMidtermDate }}
                  </div>
                  <div
                    v-if="exam.formattedExamTime"
                    class="mt-1 text-sm text-warm-600 dark:text-zinc-400"
                  >
                    {{ exam.formattedExamTime }}
                  </div>
                </template>
                <div v-else class="text-warm-500 dark:text-zinc-400">—</div>
              </div>

              <div>
                <p
                  class="mb-1 text-xs font-semibold tracking-wide text-warm-600 uppercase dark:text-zinc-400"
                >
                  期末考
                </p>
                <template v-if="exam.finalDate">
                  <div class="font-semibold text-warm-900 dark:text-zinc-100">
                    {{ exam.formattedFinalDate }}
                  </div>
                  <div
                    v-if="exam.formattedExamTime"
                    class="mt-1 text-sm text-warm-600 dark:text-zinc-400"
                  >
                    {{ exam.formattedExamTime }}
                  </div>
                </template>
                <div v-else class="text-warm-500 dark:text-zinc-400">—</div>
              </div>
            </div>
          </div>

          <div
            v-if="viewModel.exams.length === 0"
            class="px-4 py-16 text-center text-warm-500 dark:text-zinc-400"
          >
            您的課表中沒有任何科目有設定考試日期。
          </div>
        </div>

        <!-- 桌面：維持表格，但只在 md+ 顯示 -->
        <div class="hidden overflow-x-auto md:block">
          <table
            class="w-full border-collapse overflow-hidden rounded text-left text-warm-700 dark:text-zinc-300"
          >
            <thead>
              <tr
                class="rounded-t border-b-2 border-warm-300 bg-warm-100 dark:border-zinc-600 dark:bg-zinc-900"
              >
                <th
                  class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100"
                >
                  課程
                </th>
                <th
                  v-if="!viewModel.selectedTerm.endsWith('C')"
                  class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100"
                >
                  期中考
                </th>
                <th
                  class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100"
                >
                  期末考
                </th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="exam in viewModel.exams"
                :key="exam.courseId"
                class="border-b border-warm-200 hover:bg-warm-50 dark:border-zinc-700 dark:hover:bg-zinc-950"
              >
                <td
                  class="px-4 py-3 font-semibold text-warm-900 dark:text-zinc-100"
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
                      class="mr-3 inline-flex items-center gap-1 text-sm font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100 print:hidden"
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
                  <div v-else class="text-warm-500 dark:text-zinc-400">—</div>
                  <div
                    v-if="exam.formattedExamTime"
                    class="mt-1 text-sm text-warm-600 dark:text-zinc-400"
                  >
                    {{ exam.formattedExamTime }}
                  </div>
                </td>

                <td class="px-4 py-3 tabular-nums">
                  <div v-if="exam.finalDate" class="font-semibold">
                    {{ exam.formattedFinalDate }}
                  </div>
                  <div v-else class="text-warm-500 dark:text-zinc-400">—</div>
                  <div
                    v-if="exam.formattedExamTime"
                    class="mt-1 text-sm text-warm-600 dark:text-zinc-400"
                  >
                    {{ exam.formattedExamTime }}
                  </div>
                </td>
              </tr>

              <tr v-if="viewModel.exams.length === 0">
                <td
                  colspan="3"
                  class="px-4 py-16 text-center text-warm-500 dark:text-zinc-400"
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
          class="mb-8 print:hidden"
          :schedule-uuid="viewModel.uuid"
          :has-any-selection="announcementsWidget.hasAnySelection"
          :announcements="announcementsWidget.announcements"
          :more-announcements-url="announcementsWidget.moreAnnouncementsUrl"
        />
      </div>

      <PwaInstallBanner />

      <!-- Share Section -->
      <div
        v-if="viewModel.displayOptions.show_share_section"
        class="rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="flex items-center justify-between gap-4 print:flex">
          <div class="w-full md:w-auto md:flex-1 print:flex-1">
            <p class="mb-3 text-warm-700 dark:text-zinc-300">
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
              class="rounded border border-warm-300 bg-white text-sm text-warm-600 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-400"
            >
              <div class="flex items-stretch gap-3">
                <input
                  ref="shareInput"
                  class="flex-1 px-3 py-2 font-mono break-all text-warm-600 dark:text-zinc-400 print:hidden"
                  :value="copyShareUrl"
                  readonly
                  aria-label="我的課表連結"
                  @click="$event.target.select()"
                />
                <div
                  class="hidden items-center px-3 py-2 font-mono break-all text-warm-600 dark:text-zinc-400 print:flex"
                >
                  {{ copyShareUrl }}
                </div>

                <div class="shrink-0">
                  <button
                    type="button"
                    :aria-pressed="copied.toString()"
                    class="ml-2 h-full rounded-l-none rounded-r border border-warm-200 bg-warm-200 px-3 py-1 text-sm font-semibold whitespace-nowrap text-warm-900 transition hover:bg-warm-300 dark:border-zinc-700 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600 print:hidden"
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

          <div
            class="hidden w-28 flex-col items-center justify-center md:flex print:flex"
          >
            <div
              class="rounded border border-warm-200 bg-white p-2"
              v-html="qrCodeSvg"
            ></div>
          </div>
        </div>
      </div>

      <div
        v-if="viewModel.displayOptions.show_print_button"
        class="mt-6 flex justify-end print:hidden"
      >
        <button
          type="button"
          data-testid="schedule-print-button"
          class="inline-flex items-center justify-center gap-2 rounded-lg border border-warm-200 bg-warm-200 px-4 py-2 font-semibold text-warm-900 transition hover:bg-warm-300 dark:border-zinc-700 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600"
          @click="print()"
        >
          <Icon name="printer" class="inline size-4" />
          列印
        </button>
      </div>
    </div>
  </AppLayout>
</template>
