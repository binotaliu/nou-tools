<script setup>
import { computed } from 'vue'
import {
  addDays,
  dateRangeDays,
  newsletterDateParts,
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

const days = computed(() => {
  const result = []
  let date = props.highlightsFrom

  while (date <= props.highlightsTo) {
    const { month, day, weekday } = newsletterDateParts(date)
    result.push({ date, month, day, weekday })
    date = addDays(date, 1)
  }

  return result
})

const eventsByDay = computed(() => {
  const buckets = new Map()

  props.events.forEach(event => {
    dateRangeDays(event.startDate, event.endDate).forEach(date => {
      if (date < props.highlightsFrom || date > props.highlightsTo) {
        return
      }

      const bucket = buckets.get(date) ?? []
      bucket.push(event)
      buckets.set(date, bucket)
    })
  })

  return buckets
})
</script>

<template>
  <div
    class="grid grid-cols-7 gap-1 sm:gap-2"
    data-testid="newsletter-highlights-calendar"
  >
    <div
      v-for="day in days"
      :key="day.date"
      class="min-h-16 rounded-md border border-theme-200 p-1.5 text-xs sm:min-h-20 sm:p-2 sm:text-sm dark:border-zinc-700"
      :data-testid="`calendar-day-${day.date}`"
    >
      <p class="font-medium text-theme-600 dark:text-zinc-400">
        週{{ day.weekday }}・{{ day.month }}/{{ day.day }}
      </p>
      <ul class="mt-1 space-y-1">
        <li
          v-for="event in eventsByDay.get(day.date) ?? []"
          :key="event.name"
          :title="event.name"
          class="truncate rounded bg-theme-100 px-1 py-0.5 text-theme-800 dark:bg-theme-900/40 dark:text-theme-200"
        >
          {{ event.name }}
        </li>
      </ul>
    </div>
  </div>
</template>
