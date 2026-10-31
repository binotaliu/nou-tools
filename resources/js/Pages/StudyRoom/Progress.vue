<script setup>
// 專注進度: a Monday-first month calendar (a dot under every date with
// records) and the detail of the selected Monday-to-Sunday week. Month, week
// and the 面授 filter live in the query string, so every change is a partial
// Inertia visit of the `progress` prop and the URL stays shareable with
// yourself. Dates are Taipei dates throughout; the server sends `today`.
import { computed, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import IncludeInPersonToggle from '../../Components/StudyRoom/IncludeInPersonToggle.vue'
import SubjectPieChart from '../../Components/StudyRoom/SubjectPieChart.vue'
import useStudyChartFilter from '../../Composables/useStudyChartFilter'
import {
  formatCompactDurationLabel,
  formatDurationLabel,
  sessionDurationLabel,
} from '../../study-room-format'

const props = defineProps({
  progress: { type: Object, required: true },
})

const { includeInPerson } = useStudyChartFilter()

const WEEKDAYS = ['一', '二', '三', '四', '五', '六', '日']

function addDays(date, days) {
  const [y, m, d] = date.split('-').map(Number)
  const shifted = new Date(Date.UTC(y, m - 1, d + days))

  return shifted.toISOString().slice(0, 10)
}

function shiftMonth(month, delta) {
  const [y, m] = month.split('-').map(Number)

  return new Date(Date.UTC(y, m - 1 + delta, 1)).toISOString().slice(0, 7)
}

const weeks = computed(() =>
  Array.from({ length: 6 }, (_, week) => {
    const start = addDays(props.progress.gridStart, week * 7)

    return {
      start,
      days: Array.from({ length: 7 }, (_, day) => addDays(start, day)),
    }
  })
)

const activeDates = computed(() => new Set(props.progress.activeDates))
const monthLabel = computed(() => {
  const [y, m] = props.progress.month.split('-')

  return y + ' 年 ' + Number(m) + ' 月'
})
const weekRangeLabel = computed(() => {
  const format = date => {
    const [, m, d] = date.split('-')

    return Number(m) + '/' + Number(d)
  }

  return (
    format(props.progress.weekStart) +
    ' – ' +
    format(addDays(props.progress.weekStart, 6))
  )
})
const maxDaySeconds = computed(() =>
  Math.max(...props.progress.days.map(day => day.focusSeconds), 1)
)

function visit(params) {
  router.get(
    '/study-room/progress',
    {
      month: props.progress.month,
      week: props.progress.weekStart,
      ...(includeInPerson.value ? { include_in_person: 1 } : {}),
      ...params,
    },
    { only: ['progress'], preserveScroll: true, preserveState: true }
  )
}

function selectDate(date) {
  // Picking a date in an adjacent month's spill-over row also moves the month.
  visit({ week: date, month: date.slice(0, 7) })
}

function showMonth(delta) {
  visit({ month: shiftMonth(props.progress.month, delta) })
}

function shiftWeek(delta) {
  const week = addDays(props.progress.weekStart, delta * 7)

  visit({ week, month: week.slice(0, 7) })
}

function goToToday() {
  visit({ week: props.progress.today, month: props.progress.today.slice(0, 7) })
}

function dayLabel(date) {
  return Number(date.slice(8))
}

function barHeight(day) {
  return Math.max(Math.round((day.focusSeconds / maxDaySeconds.value) * 100), 4)
}

// The stored preference decides the first view; the URL is the authority after.
onMounted(() => {
  if (includeInPerson.value !== props.progress.includeInPerson) {
    visit({ include_in_person: includeInPerson.value ? 1 : 0 })
  }
})

function onToggle(value) {
  includeInPerson.value = value
  visit({ include_in_person: value ? 1 : 0 })
}
</script>

<template>
  <AppLayout>
    <Head title="專注進度" />

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-2xl font-bold text-theme-900 dark:text-zinc-100">
          專注進度
        </h1>
        <div class="flex items-center gap-4">
          <IncludeInPersonToggle
            :model-value="includeInPerson"
            testid="study-room-progress-include-in-person"
            @update:model-value="onToggle"
          />
          <Link
            href="/study-room"
            class="text-sm font-medium text-theme-700 underline dark:text-theme-300"
            >回到自習室</Link
          >
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
        <div class="space-y-6">
          <section
            class="rounded-xl border border-theme-200 p-4 dark:border-zinc-700"
            aria-labelledby="progress-calendar-heading"
          >
            <div class="mb-3 flex items-center justify-between">
              <button
                type="button"
                class="rounded-md px-3 py-2 text-sm hover:bg-theme-100 dark:hover:bg-zinc-800"
                aria-label="上個月"
                data-testid="study-room-progress-prev-month"
                @click="showMonth(-1)"
              >
                ‹
              </button>
              <h2
                id="progress-calendar-heading"
                class="font-semibold text-theme-900 dark:text-zinc-100"
                data-testid="study-room-progress-month"
              >
                {{ monthLabel }}
              </h2>
              <button
                type="button"
                class="rounded-md px-3 py-2 text-sm hover:bg-theme-100 dark:hover:bg-zinc-800"
                aria-label="下個月"
                data-testid="study-room-progress-next-month"
                @click="showMonth(1)"
              >
                ›
              </button>
            </div>

            <div
              class="grid grid-cols-7 text-center text-xs text-theme-700 dark:text-zinc-400"
            >
              <span v-for="weekday in WEEKDAYS" :key="weekday" class="py-1">{{
                weekday
              }}</span>
            </div>

            <div
              v-for="week in weeks"
              :key="week.start"
              class="grid grid-cols-7 rounded-lg"
              :class="
                week.start === progress.weekStart
                  ? 'bg-theme-100 dark:bg-zinc-800'
                  : ''
              "
              :data-testid="'study-room-progress-week-' + week.start"
            >
              <button
                v-for="date in week.days"
                :key="date"
                type="button"
                class="flex min-h-11 flex-col items-center justify-center gap-0.5 rounded-md text-sm"
                :class="[
                  date.slice(0, 7) === progress.month
                    ? 'text-theme-900 dark:text-zinc-100'
                    : 'text-theme-500 dark:text-zinc-500',
                  date === progress.today
                    ? 'font-bold ring-1 ring-theme-600 dark:ring-theme-400'
                    : '',
                ]"
                :aria-current="date === progress.today ? 'date' : null"
                :aria-label="date + (activeDates.has(date) ? '，有紀錄' : '')"
                :data-testid="'study-room-progress-day-' + date"
                @click="selectDate(date)"
              >
                <span>{{ dayLabel(date) }}</span>
                <span
                  class="size-1.5 rounded-full"
                  :class="
                    activeDates.has(date)
                      ? 'bg-theme-600 dark:bg-theme-400'
                      : 'bg-transparent'
                  "
                  :data-testid="
                    activeDates.has(date)
                      ? 'study-room-progress-dot-' + date
                      : null
                  "
                  aria-hidden="true"
                ></span>
              </button>
            </div>

            <div class="mt-3 text-right">
              <button
                type="button"
                class="text-sm font-medium text-theme-700 underline dark:text-theme-300"
                data-testid="study-room-progress-today"
                @click="goToToday"
              >
                回到本週
              </button>
            </div>
          </section>

          <section
            class="space-y-5 rounded-xl border border-theme-200 p-4 dark:border-zinc-700"
            aria-labelledby="progress-week-heading"
          >
            <div class="flex items-center justify-between gap-2">
              <button
                type="button"
                class="rounded-md px-3 py-2 text-sm hover:bg-theme-100 dark:hover:bg-zinc-800"
                @click="shiftWeek(-1)"
              >
                ‹ 上一週
              </button>
              <div class="text-center">
                <h2
                  id="progress-week-heading"
                  class="font-semibold text-theme-900 dark:text-zinc-100"
                  data-testid="study-room-progress-week-range"
                >
                  {{ weekRangeLabel }}
                </h2>
                <p
                  class="text-sm text-theme-700 dark:text-zinc-400"
                  data-testid="study-room-progress-week-total"
                >
                  共 {{ formatDurationLabel(progress.weekFocusSeconds) }}
                </p>
              </div>
              <button
                type="button"
                class="rounded-md px-3 py-2 text-sm hover:bg-theme-100 dark:hover:bg-zinc-800"
                @click="shiftWeek(1)"
              >
                下一週 ›
              </button>
            </div>

            <div class="flex items-start justify-between gap-1.5">
              <div
                v-for="day in progress.days"
                :key="day.date"
                class="flex flex-1 flex-col items-center gap-1.5 py-1.5"
              >
                <span class="flex h-24 w-full items-end justify-center">
                  <span
                    class="w-5 rounded-t-sm bg-theme-600 dark:bg-theme-400"
                    :style="{ height: barHeight(day) + '%' }"
                  ></span>
                </span>
                <span
                  class="text-xs font-medium text-theme-700 dark:text-zinc-400"
                  >{{ day.label }}</span
                >
                <span
                  class="text-[11px] whitespace-nowrap text-theme-700 dark:text-zinc-400"
                  >{{ formatCompactDurationLabel(day.focusSeconds) }}</span
                >
              </div>
            </div>

            <div class="border-t border-theme-200 pt-4 dark:border-zinc-700">
              <h3
                class="mb-3 text-sm font-semibold text-theme-900 dark:text-zinc-100"
              >
                科目分佈
              </h3>
              <SubjectPieChart
                :subjects="progress.subjects"
                testid="study-room-progress-subject-pie"
              />
            </div>
          </section>
        </div>

        <section
          class="space-y-4 rounded-xl border border-theme-200 p-4 dark:border-zinc-700"
          aria-labelledby="progress-detail-heading"
        >
          <h2
            id="progress-detail-heading"
            class="font-semibold text-theme-900 dark:text-zinc-100"
          >
            每日紀錄
          </h2>
          <div class="space-y-4">
            <div
              v-for="day in progress.days.filter(d => d.sessions.length > 0)"
              :key="day.date"
            >
              <div class="mb-1 flex items-center justify-between text-sm">
                <h3 class="font-semibold text-theme-900 dark:text-zinc-100">
                  {{ day.date.slice(5).replace('-', '/') }}（{{ day.label }}）
                </h3>
                <span class="text-theme-700 dark:text-zinc-400">{{
                  formatDurationLabel(day.focusSeconds)
                }}</span>
              </div>
              <ul
                class="space-y-1"
                :data-testid="'study-room-progress-log-' + day.date"
              >
                <li
                  v-for="session in day.sessions"
                  :key="session.startedAt"
                  class="flex items-center justify-between gap-2 text-sm"
                >
                  <span class="truncate text-theme-800 dark:text-zinc-200">{{
                    session.activityLabel
                  }}</span>
                  <span class="shrink-0 text-theme-700 dark:text-zinc-400">{{
                    sessionDurationLabel(session)
                  }}</span>
                </li>
              </ul>
            </div>
            <p
              v-if="progress.days.every(d => d.sessions.length === 0)"
              class="text-sm text-theme-700 dark:text-zinc-400"
              data-testid="study-room-progress-empty"
            >
              這週沒有紀錄
            </p>
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>
