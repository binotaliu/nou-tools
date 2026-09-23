<script setup>
// Uses the `useLearningProgress` composable for the scroll-gradient overlay
// + print-friendly form submission, and the `Greeting` component (see
// resources/js/Components/Greeting.vue) for the greeting card.
//
// Only the ViewModel's constructor properties survive Inertia's JSON
// serialization, so derived state (isVideoComplete, isWeekFullyComplete,
// getCurrentWeek, ...) is computed here from `viewModel.entries` instead.
import {
  computed,
  nextTick,
  onMounted,
  onUnmounted,
  reactive,
  ref,
  watch,
} from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { CheckIcon } from '@heroicons/vue/24/solid'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import DateField from '../../Components/DateField.vue'
import Greeting from '../../Components/Greeting.vue'
import Select from '../../Components/Select.vue'
import useLearningProgress from '../../Composables/useLearningProgress'
import useLearningProgressViewMode from '../../Composables/useLearningProgressViewMode'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
  greeting: {
    type: Object,
    required: true,
  },
})

const semesterLabel = computed(() => {
  const match = /^(\d{4})([ABC])$/.exec(props.viewModel.term)

  if (!match) {
    return props.viewModel.term
  }

  const rocYear = Number(match[1]) - 1911
  const termName = { A: '上學期', B: '下學期', C: '暑期' }[match[2]]

  return `${rocYear} 學年度${termName}`
})

const pageTitle = computed(() => {
  const suffix = props.viewModel.scheduleName
    ? ` - ${props.viewModel.scheduleName}`
    : ''

  return `學習進度表 - ${semesterLabel.value}${suffix} - NOU 小幫手`
})

// --- derived progress state (ported from LearningProgressViewModel's
// methods, which don't survive JSON serialization) ---
const entryMap = computed(() => {
  const map = new Map()

  props.viewModel.entries.forEach(entry => {
    map.set(`${entry.courseId}-${entry.weekNum}`, entry)
  })

  return map
})

function findEntry(courseId, weekNum) {
  return entryMap.value.get(`${courseId}-${weekNum}`) ?? null
}

// `progress`/`homework` are the single source of truth for every
// checkbox/textarea/deadline across all 3 mobile views (table/week/subject).
// They're seeded once from the server-provided viewModel below; from then on
// every view reads and writes these instead of the raw props, so switching
// views never loses an unsaved edit.
const progress = reactive({})
const homework = reactive({})

function seedProgressStore() {
  props.viewModel.courses.forEach(course => {
    progress[course.id] = {}
    homework[course.id] = {}

    props.viewModel.weeks.forEach(week => {
      const entry = findEntry(course.id, week.num)

      progress[course.id][week.num] = {
        video: entry?.videoCompleted ?? false,
        textbook: entry?.textbookCompleted ?? false,
        note: entry?.note ?? '',
      }
    })

    ;[1, 2].forEach(number => {
      const hw = findHomework(course.id, number)

      homework[course.id][number] = {
        completed: hw?.completed ?? false,
        deadline: hw?.deadline ?? '',
        note: hw?.note ?? '',
      }
    })
  })
}

function isVideoComplete(courseId, weekNum) {
  return progress[courseId]?.[weekNum]?.video ?? false
}

function isTextbookComplete(courseId, weekNum) {
  return progress[courseId]?.[weekNum]?.textbook ?? false
}

function isProgressComplete(courseId, weekNum) {
  return (
    isVideoComplete(courseId, weekNum) && isTextbookComplete(courseId, weekNum)
  )
}

function getNote(courseId, weekNum) {
  return progress[courseId]?.[weekNum]?.note ?? ''
}

function isWeekFullyComplete(weekNum) {
  return props.viewModel.courses.every(course =>
    isProgressComplete(course.id, weekNum)
  )
}

function hasIncompleteCourseInWeek(weekNum) {
  return props.viewModel.courses.some(
    course => !isProgressComplete(course.id, weekNum)
  )
}

const currentWeek = computed(() => {
  if (
    !props.viewModel.semesterStart ||
    !props.viewModel.semesterEnd ||
    !props.viewModel.now
  ) {
    return null
  }

  const today = new Date(props.viewModel.now)
  today.setHours(0, 0, 0, 0)

  const start = new Date(props.viewModel.semesterStart)
  const end = new Date(props.viewModel.semesterEnd)

  if (today < start || today > end) {
    return null
  }

  const diffDays = Math.round(Math.abs(today - start) / 86400000)

  return Math.floor(diffDays / 7) + 1
})

function isWeekPassed(weekNum) {
  return currentWeek.value !== null && weekNum < currentWeek.value
}

// Mirrors the table's legend (目前週次 / 進度落後) for the 依科目 mobile
// view, scoped to the single selected course rather than "any course".
function subjectWeekStatus(weekNum) {
  if (currentWeek.value === weekNum) {
    return 'current'
  }

  if (
    isWeekPassed(weekNum) &&
    !isProgressComplete(selectedCourseId.value, weekNum)
  ) {
    return 'overdue'
  }

  return null
}

function subjectCardBorderClass(weekNum) {
  const status = subjectWeekStatus(weekNum)

  if (status === 'current') {
    return 'border-blue-500 dark:border-blue-400'
  }

  if (status === 'overdue') {
    return 'border-red-400 dark:border-red-400'
  }

  return 'border-theme-200 dark:border-zinc-700'
}

function subjectCheckboxClass(weekNum, checked) {
  if (checked) {
    return 'border-theme-400 bg-theme-50 dark:border-zinc-500 dark:bg-zinc-800'
  }

  const status = subjectWeekStatus(weekNum)

  if (status === 'current') {
    return 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-950/60'
  }

  if (status === 'overdue') {
    return 'border-red-400 bg-red-50 dark:border-red-400 dark:bg-red-950/60'
  }

  return 'border-theme-200 dark:border-zinc-700'
}

// --- homework table state ---
// `homeworkEntries` is guarded because the app's service worker
// (public/sw.js) caches JS chunks independently of the page's props, so a
// visitor mid-deploy can end up running a bundle from a different build than
// the one that served this viewModel — better to render an empty homework
// section than hard-crash the whole page.
const homeworkMap = computed(() => {
  const map = new Map()

  ;(props.viewModel.homeworkEntries ?? []).forEach(entry => {
    map.set(`${entry.courseId}-${entry.number}`, entry)
  })

  return map
})

function findHomework(courseId, number) {
  return homeworkMap.value.get(`${courseId}-${number}`) ?? null
}

seedProgressStore()

function toChineseNumber(n) {
  return window.NouTime ? window.NouTime.chineseNumber(n) : String(n)
}

// --- mobile view-mode switch (table/week/subject) ---
const { viewMode, setViewMode } = useLearningProgressViewMode()

const viewModeTabs = [
  { value: 'week', label: '依週次' },
  { value: 'subject', label: '依科目' },
  { value: 'homework', label: '作業' },
  { value: 'table', label: '表格' },
]

const selectedWeekNum = ref(
  currentWeek.value ?? props.viewModel.weeks[0]?.num ?? null
)
const selectedCourseId = ref(props.viewModel.courses[0]?.id ?? null)

function homeworkLabel(number) {
  return number === 1 ? '作業一' : '作業二'
}

// --- 依週次/依科目 prev/next switches ---
// Weeks clamp at the semester's edges (no wrap); subjects wrap around, since
// there's no "first"/"last" ordering meaningful to the reader the way weeks
// have.
const selectedWeekIndex = computed(() =>
  props.viewModel.weeks.findIndex(week => week.num === selectedWeekNum.value)
)

const previousWeek = computed(() => {
  const index = selectedWeekIndex.value

  return index > 0 ? props.viewModel.weeks[index - 1] : null
})

const nextWeek = computed(() => {
  const index = selectedWeekIndex.value

  return index >= 0 && index < props.viewModel.weeks.length - 1
    ? props.viewModel.weeks[index + 1]
    : null
})

function weekNavLabel(week) {
  return `第${toChineseNumber(week.num)}週`
}

const selectedCourseIndex = computed(() =>
  props.viewModel.courses.findIndex(
    course => course.id === selectedCourseId.value
  )
)

const previousCourse = computed(() => {
  const courses = props.viewModel.courses
  const index = selectedCourseIndex.value

  if (index < 0 || courses.length === 0) {
    return null
  }

  return courses[(index - 1 + courses.length) % courses.length]
})

const nextCourse = computed(() => {
  const courses = props.viewModel.courses
  const index = selectedCourseIndex.value

  if (index < 0 || courses.length === 0) {
    return null
  }

  return courses[(index + 1) % courses.length]
})

function updateHomeworkDeadline(courseId, number, value) {
  homework[courseId][number].deadline = value
  hasUnsavedChanges.value = true
}

// --- scroll-gradient overlay + form submission (ported composable) ---
const progressForm = ref(null)

// Any edit inside the form marks it dirty; the phone PWA (which hides the
// header save button) then shows a floating save button. Saving is a native
// POST + redirect, so the flag never needs resetting.
const hasUnsavedChanges = ref(false)

const {
  showHorizontalGradient,
  showVerticalGradient,
  checkGradientVisibility,
  init,
  submitProgressForm,
} = useLearningProgress(progressForm)

// Switching back to the table view on mobile can change its scroll
// dimensions (it was `display:none` a moment ago), so re-check the
// gradients once Vue has applied the new visibility class.
watch(viewMode, () => nextTick(checkGradientVisibility))

// The week/subject card views must only ever be mounted below `md` (48rem) —
// matching CSS with a `md:hidden` class isn't enough on its own, since it
// would still mount a second DateField with the same aria-label as the
// table's, which breaks Playwright's strict-mode element matching even
// though the copy is visually hidden.
const isMobileViewport = ref(false)
let mobileMediaQuery = null

function updateIsMobileViewport(event) {
  isMobileViewport.value = event.matches
}

let scrollDebounceTimer = null

function onFormScroll() {
  clearTimeout(scrollDebounceTimer)
  scrollDebounceTimer = setTimeout(checkGradientVisibility, 100)
}

onMounted(() => {
  init()
  window.addEventListener('resize', checkGradientVisibility)

  mobileMediaQuery = window.matchMedia('(width < 48rem)')
  isMobileViewport.value = mobileMediaQuery.matches
  mobileMediaQuery.addEventListener('change', updateIsMobileViewport)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkGradientVisibility)
  clearTimeout(scrollDebounceTimer)
  mobileMediaQuery?.removeEventListener('change', updateIsMobileViewport)
})

function print() {
  window.print()
}

// The progress form is a plain native POST (see useLearningProgress's
// fallback `document.getElementById(formId)?.submit()`), not an Inertia/XHR
// request, so it needs its own CSRF token field rather than relying on the
// XSRF-TOKEN cookie Inertia's axios instance reads automatically.
const csrfToken =
  typeof document !== 'undefined'
    ? (document.querySelector('meta[name="csrf-token"]')?.content ?? '')
    : ''
</script>

<template>
  <Head :title="pageTitle" />

  <AppLayout>
    <div class="mx-auto max-w-7xl">
      <div
        class="mb-8 flex flex-col items-start justify-between gap-y-4 md:flex-row bottom-nav:hidden"
        data-testid="learning-progress-header"
      >
        <div>
          <h2 class="mb-2 text-3xl font-bold text-theme-900 dark:text-zinc-100">
            學習進度表
            <small v-if="viewModel.scheduleName"
              >— {{ viewModel.scheduleName }}</small
            >
          </h2>
          <p class="text-lg text-theme-700 dark:text-zinc-300">
            {{ semesterLabel }}
          </p>
        </div>

        <div class="flex w-full gap-2 md:w-auto print:hidden">
          <Link
            :href="`/schedules/${viewModel.scheduleUuid}`"
            class="inline-flex w-1/2 items-center justify-center gap-2 rounded-md border border-theme-200 px-4 py-2 text-sm font-medium text-theme-700 transition-colors hover:bg-theme-50 md:w-auto dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
            data-analytics-event="learning_progress_back"
            data-analytics-feature="learning_progress"
          >
            <Icon name="arrow-left" class="size-4" />
            回到課表
          </Link>

          <button
            type="button"
            class="inline-flex w-1/2 items-center justify-center gap-2 rounded-md bg-theme-700 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-theme-600 md:w-auto"
            data-analytics-event="learning_progress_save"
            data-analytics-feature="learning_progress"
            @click="submitProgressForm()"
          >
            <Icon name="check" class="size-4" />
            保存進度
          </button>
        </div>
      </div>

      <Greeting
        class="mb-6"
        :semester-label="greeting.semesterLabel"
        :semester-code="greeting.semesterCode"
        :semester-start="greeting.semesterStart"
        :semester-end="greeting.semesterEnd"
      />

      <div class="mb-4 w-full print:hidden">
        <p class="mb-1 text-sm text-theme-700 dark:text-zinc-300">
          本學期完成進度：{{ viewModel.percentage.toFixed(0) }}%
        </p>
        <div
          class="relative h-2 w-full overflow-hidden rounded bg-theme-200 dark:bg-zinc-700"
          aria-hidden="true"
        >
          <div
            class="h-full bg-theme-500"
            :style="{ width: viewModel.percentage + '%' }"
          ></div>
        </div>
      </div>

      <form
        id="progress-form"
        method="POST"
        :action="`/schedules/${viewModel.scheduleUuid}/${viewModel.term}/learning-progress`"
        :style="{
          '--courses-count': viewModel.courses.length,
          '--weeks-count': viewModel.weeks.length,
        }"
        @input="hasUnsavedChanges = true"
        @change="hasUnsavedChanges = true"
      >
        <input type="hidden" name="_method" value="PUT" />
        <input type="hidden" name="_token" :value="csrfToken" />

        <div
          class="p-2 md:hidden print:hidden"
          data-testid="learning-progress-view-switcher"
        >
          <div
            role="tablist"
            class="grid grid-cols-4 gap-1 rounded-md bg-theme-100 p-1 dark:bg-zinc-800"
          >
            <button
              v-for="tab in viewModeTabs"
              :key="tab.value"
              type="button"
              role="tab"
              :aria-selected="(viewMode === tab.value).toString()"
              class="rounded px-2 py-1.5 text-sm font-medium transition-colors"
              :class="
                viewMode === tab.value
                  ? 'bg-white text-theme-900 shadow-sm dark:bg-zinc-700 dark:text-zinc-100'
                  : 'text-theme-700 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100'
              "
              :data-testid="`learning-progress-view-tab-${tab.value}`"
              @click="setViewMode(tab.value)"
            >
              {{ tab.label }}
            </button>
          </div>
        </div>

        <div
          class="relative"
          :class="
            viewMode === 'table' || !isMobileViewport
              ? 'rounded border border-theme-300 dark:border-zinc-600'
              : ''
          "
        >
          <div
            ref="progressForm"
            :class="viewMode === 'table' ? 'block' : 'hidden md:block'"
            class="max-h-[min(45rem,90vh)] max-w-full overflow-x-auto rounded bg-linear-to-b from-theme-100 to-white dark:from-zinc-900 dark:to-zinc-950 print:max-h-full"
            @scroll="onFormScroll"
          >
            <table
              class="w-full min-w-4xl table-fixed border-collapse rounded print:min-w-0"
            >
              <thead class="print:table-header-group">
                <tr
                  class="sticky top-0 z-20 rounded-t bg-theme-100 dark:bg-zinc-900 print:static"
                >
                  <th
                    class="sticky left-0 z-30 w-24 rounded-tl border border-t-0 border-l-0 border-theme-300 bg-theme-100 px-0 py-2 text-center text-sm font-bold text-theme-900 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 print:static"
                    rowspan="2"
                  >
                    週次 \ 課程
                    <div
                      class="absolute top-full left-0 h-px w-full bg-theme-300 dark:bg-zinc-600 print:hidden"
                    ></div>
                  </th>
                  <th
                    v-for="course in viewModel.courses"
                    :key="course.id"
                    class="relative w-[calc((100%-6rem)/var(--courses-count))] border border-t-0 border-theme-300 px-2 py-2 text-center font-bold text-theme-900 last:rounded-tr last:border-r-0 dark:border-zinc-600 dark:text-zinc-100 print:static"
                    colspan="2"
                  >
                    <div class="line-clamp-2 w-full overflow-hidden text-xs">
                      {{ course.name }}
                    </div>
                    <div
                      class="absolute top-full left-0 h-px w-full bg-theme-300 dark:bg-zinc-600 print:hidden"
                    ></div>
                  </th>
                </tr>
                <tr
                  class="hidden border-b border-theme-300 bg-theme-100 dark:border-zinc-600 dark:bg-zinc-900 print:table-row"
                >
                  <template
                    v-for="course in viewModel.courses"
                    :key="course.id"
                  >
                    <th
                      class="border border-t-0 border-b-0 border-theme-300 px-0 py-1 text-center text-xs font-medium text-theme-700 dark:border-zinc-600 dark:text-zinc-300"
                    >
                      影音
                    </th>
                    <th
                      class="border border-t-0 border-b-0 border-theme-300 px-0 py-1 text-center text-xs font-medium text-theme-700 last:border-r-0 dark:border-zinc-600 dark:text-zinc-300"
                    >
                      課本
                    </th>
                  </template>
                </tr>
              </thead>
              <tbody>
                <template v-for="number in [1, 2]" :key="`homework-${number}`">
                  <tr
                    class="border-b border-theme-300 bg-theme-50 dark:border-zinc-600 dark:bg-zinc-950"
                  >
                    <td
                      class="sticky left-0 z-10 break-inside-avoid border border-b-0 border-l-0 border-theme-300 bg-theme-50 px-0 py-0 text-center text-xs font-semibold text-theme-900 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100 print:static"
                      rowspan="2"
                    >
                      {{ number === 1 ? '作業一' : '作業二' }}
                      <div
                        class="absolute top-0 left-full h-full w-px bg-theme-300 dark:bg-zinc-600 print:hidden"
                      ></div>
                    </td>

                    <template
                      v-for="course in viewModel.courses"
                      :key="course.id"
                    >
                      <td
                        class="border border-theme-300 bg-white text-center last:border-r-0 dark:border-zinc-600 dark:bg-zinc-900 [&:has(input:checked)]:bg-theme-50 dark:[&:has(input:checked)]:bg-zinc-950"
                      >
                        <label
                          class="group flex h-full w-full cursor-pointer items-center justify-center gap-1 px-2 py-3"
                        >
                          <div class="grid size-4 grid-cols-1">
                            <input
                              v-model="homework[course.id][number].completed"
                              type="checkbox"
                              :name="`homework[${course.id}][${number}][completed]`"
                              value="1"
                              :aria-label="`${course.name} ${number === 1 ? '作業一' : '作業二'}已完成`"
                              class="col-start-1 row-start-1 size-4 appearance-none rounded border border-gray-500 bg-white checked:border-gray-400 dark:bg-zinc-900 print:hidden"
                            />
                            <CheckIcon
                              class="col-start-1 row-start-1 m-0.5 size-3 text-gray-600 opacity-0 group-has-checked:opacity-100 print:hidden"
                            />
                            <div
                              class="col-start-1 row-start-1 hidden size-4 rounded border border-gray-500 bg-white dark:bg-zinc-900 print:block"
                            ></div>
                          </div>
                          <span
                            class="text-xs group-has-checked:text-gray-600 print:hidden"
                            >完成</span
                          >
                        </label>
                      </td>
                      <td
                        class="border border-theme-300 bg-white p-0 text-center last:border-r-0 dark:border-zinc-600 dark:bg-zinc-900"
                      >
                        <DateField
                          :name="`homework[${course.id}][${number}][deadline]`"
                          :model-value="homework[course.id][number].deadline"
                          :label="`${course.name} ${number === 1 ? '作業一' : '作業二'}的截止日期`"
                          :today="viewModel.now"
                          :initial-month="viewModel.semesterStart"
                          @change="
                            value =>
                              updateHomeworkDeadline(course.id, number, value)
                          "
                        />
                      </td>
                    </template>
                  </tr>
                  <tr>
                    <td
                      v-for="course in viewModel.courses"
                      :key="course.id"
                      class="border border-b-0 border-theme-300 bg-white last:border-r-0 dark:border-zinc-600 dark:bg-zinc-900 print:h-16"
                      colspan="2"
                    >
                      <textarea
                        v-model="homework[course.id][number].note"
                        :name="`homework[${course.id}][${number}][note]`"
                        placeholder="（尚未設定備註）"
                        class="m-0 h-full w-full resize-none px-2 py-2 text-xs text-theme-700 placeholder-gray-400 focus:border-blue-500 focus:outline-none dark:text-zinc-300 print:text-black print:placeholder-transparent"
                        rows="2"
                        :aria-label="`${course.name} ${number === 1 ? '作業一' : '作業二'}的備註`"
                      ></textarea>
                    </td>
                  </tr>
                </template>

                <template v-for="week in viewModel.weeks" :key="week.num">
                  <tr
                    class="border-b border-theme-300 hover:bg-theme-50 dark:border-zinc-600 dark:hover:bg-zinc-950"
                  >
                    <td
                      class="sticky left-0 z-10 break-inside-avoid border border-b-0 border-l-0 border-theme-300 px-0 py-0 font-semibold text-theme-900 dark:border-zinc-600 dark:text-zinc-100 print:static print:bg-theme-50"
                      :class="
                        currentWeek === week.num
                          ? 'bg-blue-50 dark:bg-blue-950/60'
                          : isWeekFullyComplete(week.num)
                            ? 'bg-white dark:bg-zinc-900 [&>div]:text-gray-600 dark:[&>div]:text-zinc-500'
                            : isWeekPassed(week.num) &&
                                hasIncompleteCourseInWeek(week.num)
                              ? 'bg-red-50 dark:bg-red-950/60'
                              : 'bg-theme-50 dark:bg-zinc-950'
                      "
                      rowspan="2"
                    >
                      <div
                        class="text-center text-xs font-semibold print:text-black!"
                      >
                        第{{ toChineseNumber(week.num) }}週
                      </div>
                      <div
                        class="text-center text-xs text-theme-700 dark:text-zinc-400 print:text-theme-700!"
                      >
                        {{ week.start }} - {{ week.end }}
                      </div>
                      <div
                        class="absolute top-0 left-full h-full w-px bg-theme-300 dark:bg-zinc-600 print:hidden"
                      ></div>
                    </td>

                    <template
                      v-for="course in viewModel.courses"
                      :key="course.id"
                    >
                      <td
                        class="border border-theme-300 text-center last:border-r-0 dark:border-zinc-600 [&:has(input:checked)]:bg-white dark:[&:has(input:checked)]:bg-zinc-900"
                        :class="
                          currentWeek === week.num
                            ? 'bg-blue-50 dark:bg-blue-950/60'
                            : isWeekPassed(week.num)
                              ? 'bg-red-50 dark:bg-red-950/60'
                              : 'bg-white dark:bg-zinc-900'
                        "
                      >
                        <label
                          class="group flex h-full w-full cursor-pointer items-center justify-center gap-1 px-2 py-3"
                        >
                          <div class="grid size-4 grid-cols-1">
                            <input
                              v-model="progress[course.id][week.num].video"
                              type="checkbox"
                              :name="`progress[${course.id}][${week.num}][video]`"
                              value="1"
                              :aria-label="`第${toChineseNumber(week.num)}週 ${course.name} 的影音學習進度`"
                              class="col-start-1 row-start-1 size-4 appearance-none rounded border border-gray-500 bg-white checked:border-gray-400 dark:bg-zinc-900 print:hidden"
                            />
                            <CheckIcon
                              class="col-start-1 row-start-1 m-0.5 size-3 text-gray-600 opacity-0 group-has-checked:opacity-100 print:hidden"
                            />
                            <div
                              class="col-start-1 row-start-1 hidden size-4 rounded border border-gray-500 bg-white dark:bg-zinc-900 print:block"
                            ></div>
                          </div>
                          <span
                            class="text-xs group-has-checked:text-gray-600 print:hidden"
                            >影音</span
                          >
                        </label>
                      </td>
                      <td
                        class="border border-theme-300 text-center last:border-r-0 dark:border-zinc-600 [&:has(input:checked)]:bg-white dark:[&:has(input:checked)]:bg-zinc-900"
                        :class="
                          currentWeek === week.num
                            ? 'bg-blue-50 dark:bg-blue-950/60'
                            : isWeekPassed(week.num)
                              ? 'bg-red-50 dark:bg-red-950/60'
                              : 'bg-white dark:bg-zinc-900'
                        "
                      >
                        <label
                          class="group flex h-full w-full cursor-pointer items-center justify-center gap-1 px-2 py-3"
                        >
                          <div class="grid size-4 grid-cols-1">
                            <input
                              v-model="progress[course.id][week.num].textbook"
                              type="checkbox"
                              :name="`progress[${course.id}][${week.num}][textbook]`"
                              value="1"
                              :aria-label="`第${toChineseNumber(week.num)}週 ${course.name} 的課本學習進度`"
                              class="col-start-1 row-start-1 size-4 appearance-none rounded border border-gray-500 bg-white checked:border-gray-400 dark:bg-zinc-900 print:hidden"
                            />
                            <CheckIcon
                              class="col-start-1 row-start-1 m-0.5 size-3 text-gray-600 opacity-0 group-has-checked:opacity-100 print:hidden"
                            />
                            <div
                              class="col-start-1 row-start-1 hidden size-4 rounded border border-gray-500 bg-white dark:bg-zinc-900 print:block"
                            ></div>
                          </div>
                          <span
                            class="text-xs group-has-checked:text-gray-600 print:hidden"
                            >課本</span
                          >
                        </label>
                      </td>
                    </template>
                  </tr>
                  <tr>
                    <td
                      v-for="course in viewModel.courses"
                      :key="course.id"
                      class="border border-b-0 border-theme-300 bg-white last:border-r-0 dark:border-zinc-600 dark:bg-zinc-900 print:h-16"
                      colspan="2"
                    >
                      <textarea
                        v-model="progress[course.id][week.num].note"
                        :name="`notes[${course.id}][${week.num}]`"
                        placeholder="（尚未設定目標）"
                        :class="
                          isProgressComplete(course.id, week.num)
                            ? 'text-gray-600'
                            : 'text-theme-700 dark:text-zinc-300'
                        "
                        class="m-0 h-full w-full resize-none px-2 py-2 text-xs placeholder-gray-400 focus:border-blue-500 focus:outline-none print:text-black print:placeholder-transparent"
                        rows="2"
                        :aria-label="`第${toChineseNumber(week.num)}週 ${course.name} 的學習目標與備註`"
                      ></textarea>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>

          <div
            v-if="viewMode === 'homework' && isMobileViewport"
            class="p-2 md:hidden"
            data-testid="learning-progress-homework-view"
          >
            <div class="space-y-3">
              <article
                v-for="course in viewModel.courses"
                :key="course.id"
                class="rounded-lg border border-theme-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
                data-testid="learning-progress-homework-view-course-card"
                :data-course-id="course.id"
              >
                <h3
                  class="mb-2 line-clamp-2 text-sm font-semibold text-theme-900 dark:text-zinc-100"
                >
                  {{ course.name }}
                </h3>

                <div
                  v-for="number in [1, 2]"
                  :key="number"
                  class="mb-2 last:mb-0"
                >
                  <div class="mb-1 grid grid-cols-2 gap-2">
                    <label
                      class="flex cursor-pointer items-center justify-center gap-2 rounded-md border border-theme-200 px-3 py-2.5 text-sm whitespace-nowrap text-theme-700 has-checked:border-theme-400 has-checked:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:has-checked:border-zinc-500 dark:has-checked:bg-zinc-800"
                    >
                      <input
                        v-model="homework[course.id][number].completed"
                        type="checkbox"
                        class="size-4 rounded border-gray-500"
                      />
                      {{ homeworkLabel(number) }}
                    </label>
                    <DateField
                      :model-value="homework[course.id][number].deadline"
                      :label="`${course.name} ${homeworkLabel(number)}的截止日期`"
                      :today="viewModel.now"
                      :initial-month="viewModel.semesterStart"
                      variant="box"
                      format="compact"
                      placeholder="按一下以設定期限"
                      @change="
                        value =>
                          updateHomeworkDeadline(course.id, number, value)
                      "
                    />
                  </div>
                  <textarea
                    v-model="homework[course.id][number].note"
                    placeholder="（尚未設定備註）"
                    rows="3"
                    class="w-full resize-none rounded border border-theme-200 px-2 py-2 text-xs text-theme-700 placeholder-gray-400 dark:border-zinc-700 dark:text-zinc-300"
                  ></textarea>
                </div>
              </article>
            </div>
          </div>

          <div
            v-if="viewMode === 'week' && isMobileViewport"
            class="p-2 md:hidden"
            data-testid="learning-progress-week-view"
          >
            <div
              class="sticky top-(--mobile-header-height) z-10 -mx-2 bg-theme-50 px-2 pt-2 pb-3 dark:bg-zinc-950"
            >
              <Select
                v-model.number="selectedWeekNum"
                data-testid="learning-progress-week-picker"
              >
                <option
                  v-for="week in viewModel.weeks"
                  :key="week.num"
                  :value="week.num"
                >
                  第{{ toChineseNumber(week.num) }}週（{{ week.start }} -
                  {{ week.end }}）{{
                    currentWeek === week.num ? '（本週）' : ''
                  }}
                </option>
              </Select>
            </div>

            <div
              class="mb-3 flex items-center gap-2"
              data-testid="learning-progress-week-nav"
            >
              <button
                type="button"
                class="flex flex-1 items-center justify-center gap-1 rounded-md border border-theme-200 px-3 py-2 text-sm text-theme-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300"
                data-testid="learning-progress-week-prev"
                aria-label="上一週"
                :disabled="!previousWeek"
                @click="previousWeek && (selectedWeekNum = previousWeek.num)"
              >
                <Icon name="chevron-left" class="size-4 shrink-0" />
                <span class="truncate">{{
                  previousWeek ? weekNavLabel(previousWeek) : '—'
                }}</span>
              </button>
              <button
                v-if="currentWeek !== null && currentWeek !== selectedWeekNum"
                type="button"
                class="shrink-0 rounded-md border border-theme-200 px-3 py-2 text-sm font-medium text-theme-700 dark:border-zinc-700 dark:text-zinc-300"
                data-testid="learning-progress-week-current"
                @click="selectedWeekNum = currentWeek"
              >
                本週
              </button>
              <button
                type="button"
                class="flex flex-1 items-center justify-center gap-1 rounded-md border border-theme-200 px-3 py-2 text-sm text-theme-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300"
                data-testid="learning-progress-week-next"
                aria-label="下一週"
                :disabled="!nextWeek"
                @click="nextWeek && (selectedWeekNum = nextWeek.num)"
              >
                <span class="truncate">{{
                  nextWeek ? weekNavLabel(nextWeek) : '—'
                }}</span>
                <Icon name="chevron-right" class="size-4 shrink-0" />
              </button>
            </div>

            <div class="space-y-3">
              <article
                v-for="course in viewModel.courses"
                :key="course.id"
                class="rounded-lg border border-theme-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
                data-testid="learning-progress-week-view-course-card"
                :data-course-id="course.id"
              >
                <h3
                  class="mb-2 line-clamp-2 text-sm font-semibold text-theme-900 dark:text-zinc-100"
                >
                  {{ course.name }}
                </h3>

                <div class="mb-2 grid grid-cols-2 gap-2">
                  <label
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-md border border-theme-200 px-3 py-2.5 text-sm text-theme-700 has-checked:border-theme-400 has-checked:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:has-checked:border-zinc-500 dark:has-checked:bg-zinc-800"
                  >
                    <input
                      v-model="progress[course.id][selectedWeekNum].video"
                      type="checkbox"
                      class="size-4 rounded border-gray-500"
                    />
                    影音
                  </label>
                  <label
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-md border border-theme-200 px-3 py-2.5 text-sm text-theme-700 has-checked:border-theme-400 has-checked:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:has-checked:border-zinc-500 dark:has-checked:bg-zinc-800"
                  >
                    <input
                      v-model="progress[course.id][selectedWeekNum].textbook"
                      type="checkbox"
                      class="size-4 rounded border-gray-500"
                    />
                    課本
                  </label>
                </div>

                <textarea
                  v-model="progress[course.id][selectedWeekNum].note"
                  placeholder="（尚未設定目標）"
                  rows="2"
                  class="w-full resize-none rounded border border-theme-200 px-2 py-2 text-xs text-theme-700 placeholder-gray-400 dark:border-zinc-700 dark:text-zinc-300"
                ></textarea>
              </article>
            </div>
          </div>

          <div
            v-if="viewMode === 'subject' && isMobileViewport"
            class="p-2 md:hidden"
            data-testid="learning-progress-subject-view"
          >
            <div
              class="sticky top-(--mobile-header-height) z-10 -mx-2 bg-theme-50 px-2 pt-2 pb-3 dark:bg-zinc-950"
            >
              <Select
                v-model.number="selectedCourseId"
                data-testid="learning-progress-subject-picker"
              >
                <option
                  v-for="course in viewModel.courses"
                  :key="course.id"
                  :value="course.id"
                >
                  {{ course.name }}
                </option>
              </Select>
            </div>

            <div
              class="mb-3 grid grid-cols-2 gap-2"
              data-testid="learning-progress-subject-nav"
            >
              <button
                type="button"
                class="flex items-center justify-center gap-1 rounded-md border border-theme-200 px-3 py-2 text-sm text-theme-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300"
                data-testid="learning-progress-subject-prev"
                aria-label="上一科"
                :disabled="!previousCourse"
                @click="
                  previousCourse && (selectedCourseId = previousCourse.id)
                "
              >
                <Icon name="chevron-left" class="size-4 shrink-0" />
                <span class="truncate">{{
                  previousCourse ? previousCourse.name : '—'
                }}</span>
              </button>
              <button
                type="button"
                class="flex items-center justify-center gap-1 rounded-md border border-theme-200 px-3 py-2 text-sm text-theme-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300"
                data-testid="learning-progress-subject-next"
                aria-label="下一科"
                :disabled="!nextCourse"
                @click="nextCourse && (selectedCourseId = nextCourse.id)"
              >
                <span class="truncate">{{
                  nextCourse ? nextCourse.name : '—'
                }}</span>
                <Icon name="chevron-right" class="size-4 shrink-0" />
              </button>
            </div>

            <div class="space-y-3">
              <article
                v-for="week in viewModel.weeks"
                :key="week.num"
                class="rounded-lg border bg-white p-4 dark:bg-zinc-900"
                :class="subjectCardBorderClass(week.num)"
                data-testid="learning-progress-subject-view-week-row"
                :data-week-num="week.num"
              >
                <div class="mb-2 flex items-center justify-between">
                  <span
                    class="text-sm font-semibold text-theme-900 dark:text-zinc-100"
                  >
                    第{{ toChineseNumber(week.num) }}週
                  </span>
                  <span class="text-xs text-theme-700 dark:text-zinc-400">
                    {{ week.start }} - {{ week.end }}
                  </span>
                </div>

                <div class="mb-2 grid grid-cols-2 gap-2">
                  <label
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-md border px-3 py-2.5 text-sm text-theme-700 dark:text-zinc-300"
                    :class="
                      subjectCheckboxClass(
                        week.num,
                        progress[selectedCourseId][week.num].video
                      )
                    "
                  >
                    <input
                      v-model="progress[selectedCourseId][week.num].video"
                      type="checkbox"
                      class="size-4 rounded border-gray-500"
                    />
                    影音
                  </label>
                  <label
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-md border px-3 py-2.5 text-sm text-theme-700 dark:text-zinc-300"
                    :class="
                      subjectCheckboxClass(
                        week.num,
                        progress[selectedCourseId][week.num].textbook
                      )
                    "
                  >
                    <input
                      v-model="progress[selectedCourseId][week.num].textbook"
                      type="checkbox"
                      class="size-4 rounded border-gray-500"
                    />
                    課本
                  </label>
                </div>

                <textarea
                  v-model="progress[selectedCourseId][week.num].note"
                  placeholder="（尚未設定目標）"
                  rows="2"
                  class="w-full resize-none rounded border border-theme-200 px-2 py-2 text-xs text-theme-700 placeholder-gray-400 dark:border-zinc-700 dark:text-zinc-300"
                ></textarea>
              </article>
            </div>
          </div>

          <div
            class="pointer-events-none absolute bottom-0 left-0 z-20 h-16 w-full rounded-b bg-linear-to-t from-stone-900/20 to-transparent transition-opacity duration-150 ease-in md:h-32 print:hidden"
            :class="showHorizontalGradient ? 'opacity-100' : 'opacity-0'"
          ></div>

          <div
            class="pointer-events-none absolute top-0 right-0 z-20 h-full w-16 rounded-r bg-linear-to-l from-stone-900/20 to-transparent transition-opacity duration-150 ease-in md:w-32 print:hidden"
            :class="showVerticalGradient ? 'opacity-100' : 'opacity-0'"
          ></div>
        </div>
      </form>

      <div class="mt-6 flex items-start justify-between">
        <div
          class="bg-theme-50 dark:bg-zinc-950 print:hidden"
          aria-hidden="true"
        >
          <p
            class="mb-2 text-sm font-semibold text-theme-900 dark:text-zinc-100"
          >
            圖例：
          </p>
          <div class="flex items-center justify-start gap-4">
            <div class="flex items-center gap-2">
              <div
                class="size-3 rounded border-2 border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-950/60"
              ></div>
              <span class="text-xs text-theme-700 dark:text-zinc-300"
                >目前週次</span
              >
            </div>
            <div class="flex items-center gap-2">
              <div
                class="size-3 rounded border-2 border-red-400 bg-red-50 dark:border-red-400 dark:bg-red-950/60"
              ></div>
              <span class="text-xs text-red-700 dark:text-red-400"
                >進度落後（未完成）</span
              >
            </div>
          </div>
        </div>
        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-md border border-theme-200 px-4 py-2 text-sm font-medium text-theme-700 transition-colors hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950 print:hidden"
          @click="print()"
        >
          <Icon name="printer" class="inline size-4" />
          列印
        </button>
      </div>

      <div
        v-if="hasUnsavedChanges"
        class="pointer-events-none fixed inset-x-0 bottom-(--pwa-nav-height) z-30 hidden justify-center px-4 pb-4 print:hidden bottom-nav:flex"
      >
        <button
          type="button"
          class="pointer-events-auto inline-flex items-center justify-center gap-2 rounded-full bg-theme-700 px-6 py-3 text-sm font-medium text-white shadow-lg transition-colors hover:bg-theme-600"
          data-testid="learning-progress-floating-save"
          data-analytics-event="learning_progress_save"
          data-analytics-feature="learning_progress"
          @click="submitProgressForm()"
        >
          <Icon name="check" class="size-4" />
          保存進度
        </button>
      </div>
    </div>
  </AppLayout>
</template>
