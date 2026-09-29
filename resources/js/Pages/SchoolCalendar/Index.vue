<script setup>
import { computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import useSchoolCalendar from '../../Composables/useSchoolCalendar'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const { showTaipeiHint, activeEvents, shortDateRange } = useSchoolCalendar(
  () => props.viewModel.events,
  true
)

// Events arrive sorted by start date, so grouping keeps chronological order.
const months = computed(() => {
  const groups = []

  for (const event of activeEvents.value) {
    const [year, month] = event.start.split('-').map(Number)
    const label = `${year} 年 ${month} 月`
    let group = groups.at(-1)

    if (!group || group.label !== label) {
      group = { label, events: [] }
      groups.push(group)
    }

    group.events.push(event)
  }

  return groups
})

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

        <div class="relative sm:w-64">
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

      <p
        v-if="!months.length"
        class="rounded-lg border border-theme-200 bg-white p-6 text-theme-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400"
      >
        {{ viewModel.termLabel }}尚無行事曆資料。
      </p>

      <section
        v-for="month in months"
        :key="month.label"
        class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <h3
          class="mb-2 text-lg font-semibold text-theme-900 dark:text-zinc-100"
        >
          {{ month.label }}
        </h3>

        <ul>
          <li
            v-for="event in month.events"
            :key="event.start + event.name"
            :data-testid="`school-calendar-event-${event.status}`"
            class="flex flex-col-reverse items-start justify-between gap-x-2 gap-y-1 border-b border-theme-100 py-2 last:border-0 sm:flex-row sm:items-center dark:border-zinc-800"
          >
            <span class="flex flex-wrap items-center gap-2">
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
              <span
                v-if="event.important"
                class="inline-flex items-center rounded bg-theme-100 px-2 py-0.5 text-xs font-medium text-theme-800 dark:bg-zinc-800 dark:text-zinc-200"
              >
                重點
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
