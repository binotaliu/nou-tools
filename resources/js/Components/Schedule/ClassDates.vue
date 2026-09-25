<script setup>
// The 面授日期 card on the schedule page: one card with its title, a month picker, a calendar
// with a dot on every date that has a class, and the selected month's
// courses underneath. Every month's list stays in the DOM (hidden unless
// selected).
import { computed, ref, watch } from 'vue'
import ClassCode from '../ClassCode.vue'
import Icon from '../Icon.vue'

const props = defineProps({
  // `viewModel.months`: [{ monthKey: 'YYYY-MM', monthDisplay, dates: [...] }]
  // sorted by month, only months that have at least one class.
  months: { type: Array, required: true },
  hasAnyOverride: { type: Boolean, default: false },
})

// Monday-first, matching DateField.
const WEEKDAYS = ['一', '二', '三', '四', '五', '六', '日']

const pad = n => String(n).padStart(2, '0')

// Every month from the first class to the last, including the gaps, so a
// month without classes is still reachable in the calendar.
const monthTabs = computed(() => {
  if (props.months.length === 0) {
    return []
  }

  const byKey = new Map(props.months.map(month => [month.monthKey, month]))
  const [firstYear, firstMonth] = props.months[0].monthKey
    .split('-')
    .map(Number)
  const [lastYear, lastMonth] = props.months
    .at(-1)
    .monthKey.split('-')
    .map(Number)
  const tabs = []

  for (
    let index = firstYear * 12 + firstMonth - 1;
    index <= lastYear * 12 + lastMonth - 1;
    index++
  ) {
    const year = Math.floor(index / 12)
    const month = (index % 12) + 1
    const key = `${year}-${pad(month)}`

    tabs.push({ key, year, month, data: byKey.get(key) ?? null })
  }

  return tabs
})

// Taipei's date, not the viewer's: classes are published in Taipei time.
const todayYmd = window.NouTime.taipeiYmd(new Date())

// The current month, clamped into the semester's range so a past or future
// term opens on its nearest month instead of an empty calendar.
function defaultMonthKey() {
  const tabs = monthTabs.value

  if (tabs.length === 0) {
    return null
  }

  const current = todayYmd.slice(0, 7)

  if (current < tabs[0].key) {
    return tabs[0].key
  }

  if (current > tabs.at(-1).key) {
    return tabs.at(-1).key
  }

  return current
}

const selectedKey = ref(defaultMonthKey())

watch(
  () => props.months.map(month => month.monthKey).join(),
  () => {
    selectedKey.value = defaultMonthKey()
  }
)

const selectedTab = computed(
  () => monthTabs.value.find(tab => tab.key === selectedKey.value) ?? null
)

const cells = computed(() => {
  const tab = selectedTab.value

  if (!tab) {
    return []
  }

  const leading =
    (new Date(Date.UTC(tab.year, tab.month - 1, 1)).getUTCDay() + 6) % 7
  const total = new Date(Date.UTC(tab.year, tab.month, 0)).getUTCDate()
  const counts = new Map(
    (tab.data?.dates ?? []).map(date => [date.dateKey, date.courses.length])
  )

  return [
    ...Array.from({ length: leading }, () => null),
    ...Array.from({ length: total }, (_, index) => {
      const day = index + 1
      const iso = `${tab.key}-${pad(day)}`

      return { day, iso, count: counts.get(iso) ?? 0 }
    }),
  ]
})
</script>

<template>
  <div
    data-testid="schedule-class-dates"
    class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
  >
    <div class="mb-4">
      <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
        面授日期
      </h2>
      <div
        v-if="hasAnyOverride"
        class="flex items-center gap-1 text-sm text-theme-700 dark:text-zinc-400"
      >
        <Icon name="exclamation-triangle" class="size-4 text-theme-700" />
        表示該次面授時間與一般時間不同
      </div>
    </div>

    <div
      role="group"
      aria-label="選擇月份"
      class="-mx-1 mb-4 flex gap-1 overflow-x-auto px-1 pb-1"
    >
      <button
        v-for="tab in monthTabs"
        :key="tab.key"
        type="button"
        :data-testid="`class-dates-month-${tab.key}`"
        :aria-pressed="tab.key === selectedKey"
        :aria-label="`${tab.year} 年 ${tab.month} 月`"
        class="relative shrink-0 rounded-full px-4 py-1.5 text-sm font-semibold whitespace-nowrap transition focus:ring-2 focus:ring-blue-500 focus:outline-none"
        :class="
          tab.key === selectedKey
            ? 'bg-theme-700 text-white dark:bg-zinc-200 dark:text-zinc-900'
            : tab.data
              ? 'text-theme-800 hover:bg-theme-100 dark:text-zinc-200 dark:hover:bg-zinc-800'
              : 'text-theme-700 hover:bg-theme-100 dark:text-zinc-400 dark:hover:bg-zinc-800'
        "
        @click="selectedKey = tab.key"
      >
        {{ tab.month }} 月
      </button>
    </div>

    <div class="grid gap-6 md:grid-cols-[auto_1fr] md:gap-8">
      <div v-if="selectedTab" class="mx-auto w-full max-w-xs">
        <div
          class="mb-2 text-center text-sm font-semibold text-theme-900 dark:text-zinc-100"
        >
          {{ selectedTab.year }} 年 {{ selectedTab.month }} 月
        </div>

        <div
          class="grid grid-cols-7 text-center text-xs text-theme-700 dark:text-zinc-400"
        >
          <div v-for="weekday in WEEKDAYS" :key="weekday" class="py-1">
            {{ weekday }}
          </div>
        </div>

        <div class="grid grid-cols-7 gap-y-1" data-testid="class-dates-grid">
          <template v-for="(cell, index) in cells" :key="index">
            <div v-if="!cell"></div>
            <div
              v-else
              data-testid="class-dates-day"
              :data-date="cell.iso"
              :data-has-classes="cell.count > 0"
              class="mx-auto flex h-10 w-9 flex-col items-center justify-center rounded-lg text-sm"
              :class="[
                cell.count > 0
                  ? 'font-semibold text-theme-900 dark:text-zinc-100'
                  : 'text-theme-700 dark:text-zinc-400',
                cell.iso === todayYmd
                  ? 'ring-1 ring-theme-500 dark:ring-zinc-400'
                  : '',
              ]"
            >
              <span>{{ cell.day }}</span>
              <span
                class="mt-0.5 size-1.5 rounded-full"
                :class="cell.count > 0 ? 'bg-theme-600 dark:bg-theme-500' : ''"
                aria-hidden="true"
              ></span>
              <span v-if="cell.count > 0" class="sr-only">
                ，{{ cell.count }} 堂面授
              </span>
            </div>
          </template>
        </div>
      </div>

      <div class="min-w-0">
        <section
          v-for="tab in monthTabs"
          :key="tab.key"
          :data-testid="`class-dates-list-${tab.key}`"
          :class="tab.key === selectedKey ? '' : 'hidden'"
        >
          <p
            v-if="!tab.data"
            class="py-6 text-center text-sm text-theme-700 dark:text-zinc-400"
          >
            這個月沒有面授
          </p>

          <div v-else class="space-y-3">
            <div
              v-for="date in tab.data.dates"
              :key="date.dateKey"
              class="break-inside-avoid-page border-l-4 border-theme-500 py-2 pl-4"
            >
              <div class="mb-1 font-semibold text-theme-900 dark:text-zinc-100">
                {{ date.formattedDate }}
              </div>
              <div class="space-y-1">
                <div
                  v-for="(course, i) in date.courses"
                  :key="i"
                  class="text-sm text-theme-700 dark:text-zinc-300"
                >
                  <span class="font-semibold">{{ course.courseName }}</span>
                  <ClassCode
                    :code="course.isTentative ? '尚未分班' : course.code"
                  />
                  <br />
                  <span
                    class="inline-flex items-center gap-1 text-theme-700 dark:text-zinc-400"
                  >
                    {{ course.time }}
                    <Icon
                      v-if="course.hasOverride"
                      name="exclamation-triangle"
                      class="size-4 text-theme-700 dark:text-zinc-400"
                      title="該次課程時間與一般時間不同"
                    />
                  </span>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>
