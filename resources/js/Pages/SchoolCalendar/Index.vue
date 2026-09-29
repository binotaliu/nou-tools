<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Button from '../../Components/Button.vue'
import Icon from '../../Components/Icon.vue'
import EventCalendar from '../../Components/EventCalendar.vue'
import useSchoolCalendar from '../../Composables/useSchoolCalendar'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const { today, showTaipeiHint, activeEvents, shortDateRange } =
  useSchoolCalendar(() => props.viewModel.events, true)

function monthKey(year, month) {
  return `${year}-${String(month).padStart(2, '0')}`
}

// Events arrive sorted by start date, so grouping keeps chronological order.
const months = computed(() => {
  const groups = []

  for (const event of activeEvents.value) {
    const [year, month] = event.start.split('-').map(Number)
    const label = `${year} 年 ${month} 月`
    let group = groups.at(-1)

    if (!group || group.label !== label) {
      group = { key: monthKey(year, month), label, events: [] }
      groups.push(group)
    }

    group.events.push(event)
  }

  return groups
})

const VIEW_KEY = 'nou:school-calendar:view:v1'

const view = ref('calendar')

onMounted(() => {
  try {
    if (localStorage.getItem(VIEW_KEY) === 'list') {
      view.value = 'list'
    }
  } catch {
    // Storage can be blocked; the calendar view is a fine default.
  }
})

function setView(next) {
  view.value = next

  try {
    localStorage.setItem(VIEW_KEY, next)
  } catch {
    // Not remembering the choice is harmless.
  }
}

// One entry per calendar month between the first event's start and the last
// event's end. Unlike the list (grouped by start month) an event appears in
// every month it touches, so a break that spans a month end is drawn in both.
// `events` keeps the list order, so a bar's number is its row's number.
const calendarMonths = computed(() => {
  if (!activeEvents.value.length) {
    return []
  }

  const first = activeEvents.value[0].start
  const last = activeEvents.value.reduce(
    (latest, event) => (event.end > latest ? event.end : latest),
    first
  )
  const [lastYear, lastMonth] = last.split('-').map(Number)
  let [year, month] = first.split('-').map(Number)
  const result = []

  while (year < lastYear || (year === lastYear && month <= lastMonth)) {
    const from = `${year}-${String(month).padStart(2, '0')}-01`
    const lastDay = new Date(Date.UTC(year, month, 0)).getUTCDate()
    const to = `${year}-${String(month).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`
    const events = activeEvents.value.filter(
      event => event.start <= to && event.end >= from
    )

    if (events.length) {
      result.push({
        key: monthKey(year, month),
        label: `${year} 年 ${month} 月`,
        from,
        to,
        events,
        calendarEvents: events.map(event => ({
          name: event.name,
          startDate: event.start,
          endDate: event.end,
        })),
      })
    }

    month += 1

    if (month > 12) {
      month = 1
      year += 1
    }
  }

  return result
})

const displayedMonths = computed(() =>
  view.value === 'calendar' ? calendarMonths.value : months.value
)

const EXPANDED_KEY = 'nou:school-calendar:expanded:v1'

// Show every month at once instead of one at a time.
const expanded = ref(false)

onMounted(() => {
  try {
    expanded.value = localStorage.getItem(EXPANDED_KEY) === '1'
  } catch {
    // Storage can be blocked; one month at a time is a fine default.
  }
})

function setExpanded(next) {
  expanded.value = next

  try {
    localStorage.setItem(EXPANDED_KEY, next ? '1' : '0')
  } catch {
    // Not remembering the choice is harmless.
  }
}

// The month containing today, else the next one that has anything, else the
// last (a finished term). Keyed rather than indexed so it survives switching
// between the list and calendar views, whose month sets differ.
const currentMonthKey = computed(() => {
  const todayKey = today.value.slice(0, 7)
  const keys = displayedMonths.value.map(month => month.key)

  return keys.find(key => key >= todayKey) ?? keys.at(-1) ?? null
})

const selectedKey = ref(null)

watch(
  () => props.viewModel.term,
  () => {
    selectedKey.value = null
  }
)

const selectedIndex = computed(() => {
  const index = displayedMonths.value.findIndex(
    month => month.key === selectedKey.value
  )

  return index === -1
    ? displayedMonths.value.findIndex(
        month => month.key === currentMonthKey.value
      )
    : index
})

const onCurrentMonth = computed(
  () =>
    displayedMonths.value[selectedIndex.value]?.key === currentMonthKey.value
)

const visibleMonths = computed(() =>
  expanded.value
    ? displayedMonths.value
    : displayedMonths.value.slice(selectedIndex.value, selectedIndex.value + 1)
)

const canGoPrevious = computed(() => selectedIndex.value > 0)
const canGoNext = computed(
  () => selectedIndex.value < displayedMonths.value.length - 1
)

function goToMonth(offset) {
  const target = displayedMonths.value[selectedIndex.value + offset]

  if (target) {
    selectedKey.value = target.key
  }
}

function changeTerm(event) {
  router.get(
    '/school-calendar',
    { term: event.target.value },
    { preserveScroll: true }
  )
}
</script>

<template>
  <Head title="學校行事曆 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-6">
      <div
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
      >
        <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          學校行事曆
        </h2>

        <div class="flex items-center gap-2">
          <div
            class="inline-flex shrink-0 rounded-lg border border-zinc-500 p-0.5"
            role="group"
            aria-label="檢視方式"
          >
            <button
              v-for="option in [
                { key: 'list', label: '列表' },
                { key: 'calendar', label: '月曆' },
              ]"
              :key="option.key"
              type="button"
              class="h-9 rounded-md px-3 text-sm font-medium"
              :class="
                view === option.key
                  ? 'bg-theme-700 text-white dark:bg-theme-300 dark:text-zinc-900'
                  : 'text-theme-800 hover:bg-theme-50 dark:text-zinc-200 dark:hover:bg-zinc-800'
              "
              :aria-pressed="view === option.key"
              :data-testid="`school-calendar-view-${option.key}`"
              @click="setView(option.key)"
            >
              {{ option.label }}
            </button>
          </div>

          <div class="relative min-w-0 flex-1 sm:w-64 sm:flex-none">
            <label for="school-calendar-term" class="sr-only">選擇學期</label>
            <select
              id="school-calendar-term"
              data-testid="school-calendar-term"
              data-offline-disable
              class="h-10 w-full appearance-none rounded-lg border border-zinc-500 bg-white px-3 dark:border-zinc-500 dark:bg-zinc-900"
              :value="viewModel.term"
              @change="changeTerm"
            >
              <option
                v-for="term in viewModel.terms"
                :key="term.code"
                :value="term.code"
              >
                {{ term.label }}
              </option>
            </select>
            <div
              class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
            >
              <Icon name="chevron-down" class="size-5 text-zinc-400" />
            </div>
          </div>
        </div>
      </div>

      <p
        v-if="!displayedMonths.length"
        class="rounded-lg border border-theme-200 bg-white p-6 text-theme-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400"
      >
        {{ viewModel.termLabel }}尚無行事曆資料。
      </p>

      <div v-if="displayedMonths.length" class="flex items-center gap-2">
        <Button
          v-if="!expanded && !onCurrentMonth"
          size="sm"
          data-testid="school-calendar-current-month"
          @click="selectedKey = currentMonthKey"
        >
          回到本月
        </Button>

        <Button
          size="sm"
          class="ml-auto"
          :pressed="expanded"
          data-testid="school-calendar-expand-all"
          @click="setExpanded(!expanded)"
        >
          {{ expanded ? '按月份檢視' : '展開全部月份' }}
        </Button>
      </div>

      <section
        v-for="month in visibleMonths"
        :key="`${view}-${month.label}`"
        class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        :data-testid="`school-calendar-section-${month.key}`"
      >
        <div class="mb-2 flex items-center justify-between gap-2">
          <Button
            v-if="!expanded"
            variant="icon"
            class="border border-zinc-500"
            aria-label="上個月"
            :disabled="!canGoPrevious"
            data-testid="school-calendar-previous"
            @click="goToMonth(-1)"
          >
            <Icon name="chevron-left" class="size-5" />
          </Button>

          <h3
            class="text-lg font-semibold text-theme-900 dark:text-zinc-100"
            :class="{ 'flex-1 text-center': !expanded }"
            :aria-live="expanded ? undefined : 'polite'"
          >
            {{ month.label }}
          </h3>

          <Button
            v-if="!expanded"
            variant="icon"
            class="border border-zinc-500"
            aria-label="下個月"
            :disabled="!canGoNext"
            data-testid="school-calendar-next"
            @click="goToMonth(1)"
          >
            <Icon name="chevron-right" class="size-5" />
          </Button>
        </div>

        <EventCalendar
          v-if="view === 'calendar'"
          :highlights-from="month.from"
          :highlights-to="month.to"
          :events="month.calendarEvents"
          :days="viewModel.days"
          :today="today"
          test-id="school-calendar-month"
          class="mb-3"
        />

        <ul>
          <li
            v-for="(event, eventIndex) in month.events"
            :key="event.start + event.name"
            :data-testid="`school-calendar-event-${event.status}`"
            class="flex flex-col-reverse items-start justify-between gap-x-2 gap-y-1 border-b border-theme-100 py-2 last:border-0 sm:flex-row sm:items-center dark:border-zinc-800"
          >
            <span class="flex flex-wrap items-center gap-2">
              <!-- On narrow screens the calendar's bars show only this number. -->
              <span
                v-if="view === 'calendar'"
                class="inline-flex size-5 shrink-0 items-center justify-center rounded bg-theme-200 text-xs font-medium text-theme-900 sm:hidden dark:bg-theme-800/70 dark:text-theme-100"
                aria-hidden="true"
              >
                {{ eventIndex + 1 }}
              </span>
              <!-- Past events get the quieter theme-700 / zinc-400 instead of
                   an opacity fade, which would drop the text below 4.5:1. -->
              <span
                :class="
                  event.status === 'past'
                    ? 'text-theme-700 dark:text-zinc-400'
                    : 'text-theme-800 dark:text-zinc-200'
                "
                class="font-medium"
              >
                {{ event.name }}
              </span>
            </span>

            <span
              class="flex flex-col-reverse items-start gap-x-2 text-sm text-theme-700 tabular-nums sm:flex-row sm:items-center dark:text-zinc-400"
            >
              <span
                v-if="event.status === 'ongoing'"
                class="inline-flex shrink-0 items-center rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800"
              >
                進行中
              </span>
              <span
                v-else-if="event.status === 'past'"
                class="shrink-0 text-xs"
              >
                已結束
              </span>

              <span class="shrink-0">{{ shortDateRange(event) }}</span>
            </span>
          </li>
        </ul>
      </section>

      <p
        v-if="showTaipeiHint"
        class="text-xs text-theme-700 dark:text-zinc-400"
      >
        此頁日期皆為台灣時間（Asia/Taipei）
      </p>
    </div>
  </AppLayout>
</template>
