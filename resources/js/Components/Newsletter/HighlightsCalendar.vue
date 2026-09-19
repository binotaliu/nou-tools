<script setup>
// A Monday-first month-style calendar: one row per week, with each event drawn
// as a bar across the days it spans (split at week boundaries, like a real
// calendar). Bars carry the event name from `sm` up; on narrow screens the
// columns are too thin for text, so a bar shows the event's number instead and
// the list under the calendar (numbered the same way) is the legend.
import { computed } from 'vue'
import {
  addDays,
  dateRangeDays,
  newsletterDateParts,
  newsletterWeekdayIndex,
} from '../../Composables/useNewsletterDates'

const props = defineProps({
  highlightsFrom: {
    type: String,
    required: true,
  },
  highlightsTo: {
    type: String,
    required: true,
  },
  events: {
    type: Array,
    required: true,
  },
})

const WEEKDAY_LABELS = ['一', '二', '三', '四', '五', '六', '日']

// Pad the window out to whole Monday–Sunday weeks; padding days are dimmed.
const gridFrom = computed(() =>
  addDays(props.highlightsFrom, -newsletterWeekdayIndex(props.highlightsFrom))
)

const gridTo = computed(() =>
  addDays(props.highlightsTo, 6 - newsletterWeekdayIndex(props.highlightsTo))
)

function buildBars(weekDates) {
  const weekStart = weekDates[0]
  const weekEnd = weekDates[6]

  const segments = props.events
    .map((event, index) => ({ event, number: index + 1 }))
    .filter(
      ({ event }) => event.startDate <= weekEnd && event.endDate >= weekStart
    )
    .map(({ event, number }) => {
      const from = event.startDate < weekStart ? weekStart : event.startDate
      const to = event.endDate > weekEnd ? weekEnd : event.endDate
      const col = newsletterWeekdayIndex(from)

      return {
        event,
        number,
        col,
        span: newsletterWeekdayIndex(to) - col + 1,
        startsHere: event.startDate >= weekStart,
        endsHere: event.endDate <= weekEnd,
        lane: 0,
      }
    })
    .sort((a, b) => a.col - b.col || b.span - a.span || a.number - b.number)

  // Greedy lane packing: first lane whose last bar ends before this one starts.
  const laneEnds = []

  segments.forEach(segment => {
    let lane = laneEnds.findIndex(end => end < segment.col)

    if (lane === -1) {
      lane = laneEnds.length
    }

    laneEnds[lane] = segment.col + segment.span - 1
    segment.lane = lane
  })

  return { bars: segments, laneCount: laneEnds.length }
}

function dayLabelClass(day, dayIndex) {
  if (dayIndex >= 5) {
    return day.inWindow
      ? 'text-red-600 dark:text-red-400'
      : 'text-red-300 dark:text-red-900'
  }

  return day.inWindow
    ? 'text-theme-800 dark:text-zinc-200'
    : 'text-theme-400 dark:text-zinc-500'
}

const weeks = computed(() => {
  const result = []

  for (let start = gridFrom.value; start <= gridTo.value;) {
    const dates = dateRangeDays(start, addDays(start, 6))

    result.push({
      start,
      days: dates.map(date => {
        const { month, day } = newsletterDateParts(date)

        return {
          date,
          label: day === 1 || date === gridFrom.value ? `${month}/${day}` : day,
          inWindow: date >= props.highlightsFrom && date <= props.highlightsTo,
        }
      }),
      ...buildBars(dates),
    })

    start = addDays(start, 7)
  }

  return result
})
</script>

<template>
  <div
    class="overflow-hidden rounded-lg border border-theme-200 dark:border-zinc-700"
    data-testid="newsletter-highlights-calendar"
  >
    <div
      class="grid grid-cols-7 border-b border-theme-200 bg-theme-50 text-center text-xs font-medium dark:border-zinc-700 dark:bg-zinc-800"
      aria-hidden="true"
    >
      <span
        v-for="(label, labelIndex) in WEEKDAY_LABELS"
        :key="label"
        class="py-1.5"
        :class="
          labelIndex >= 5
            ? 'text-red-600 dark:text-red-400'
            : 'text-theme-600 dark:text-zinc-400'
        "
      >
        {{ label }}
      </span>
    </div>

    <!-- Every week row is stretched to the tallest one. -->
    <div class="grid auto-rows-fr">
      <div
        v-for="(week, weekIndex) in weeks"
        :key="week.start"
        class="grid grid-cols-7 border-t border-theme-200 dark:border-zinc-700"
        :style="{
          gridTemplateRows: `auto repeat(${week.laneCount}, auto) minmax(0.375rem, 1fr)`,
        }"
        :data-testid="`calendar-week-${weekIndex}`"
      >
        <!-- Cell backgrounds and dividers, spanning every row of the week. -->
        <div
          v-for="(day, dayIndex) in week.days"
          :key="`cell-${day.date}`"
          class="border-theme-200 dark:border-zinc-700"
          :class="[
            dayIndex > 0 ? 'border-l' : '',
            day.inWindow ? '' : 'bg-theme-50/70 dark:bg-zinc-800/60',
          ]"
          :style="{ gridColumn: dayIndex + 1, gridRow: '1 / -1' }"
          :data-testid="`calendar-day-${day.date}`"
          aria-hidden="true"
        ></div>

        <p
          v-for="(day, dayIndex) in week.days"
          :key="`label-${day.date}`"
          class="relative px-1.5 pt-1 pb-1 text-xs font-medium sm:px-2 sm:text-sm"
          :class="dayLabelClass(day, dayIndex)"
          :style="{ gridColumn: dayIndex + 1, gridRow: 1 }"
        >
          {{ day.label }}
        </p>

        <div
          v-for="bar in week.bars"
          :key="`bar-${bar.number}`"
          class="relative flex min-h-5 items-center bg-theme-200 px-1 py-0.5 text-[11px] leading-tight font-medium text-theme-900 sm:min-h-6 sm:px-2 sm:text-xs dark:bg-theme-800/70 dark:text-theme-100"
          :class="[
            bar.startsHere ? 'ml-1 rounded-l-md' : 'ml-0',
            bar.endsHere ? 'mr-1 rounded-r-md' : 'mr-0',
            bar.lane > 0 ? 'mt-0.5' : '',
          ]"
          :style="{
            gridColumn: `${bar.col + 1} / span ${bar.span}`,
            gridRow: bar.lane + 2,
          }"
          :title="bar.event.name"
          :data-testid="`calendar-event-${bar.number}-${weekIndex}`"
        >
          <span class="sm:hidden" aria-hidden="true">{{ bar.number }}</span>
          <span class="sr-only sm:not-sr-only">{{ bar.event.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
