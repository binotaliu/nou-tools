<script setup>
// See useSchoolCalendar.js for the calendar logic.
import useSchoolCalendar from '../Composables/useSchoolCalendar'

const props = defineProps({
  events: {
    type: Array,
    required: true,
  },
  showPastEvents: {
    type: Boolean,
    default: false,
  },
})

const {
  showTaipeiHint,
  activeEvents,
  countdownEvent,
  isCountdownMatch,
  dateRange,
  shortDateRange,
} = useSchoolCalendar(props.events, props.showPastEvents)
</script>

<template>
  <div
    v-if="events.length > 0"
    class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
  >
    <div class="mb-4">
      <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
        學校行事曆
      </h2>
    </div>

    <div class="flex flex-col md:flex-row md:items-start md:gap-6">
      <!-- Countdown (mobile 上方，桌面右側 1/3) -->
      <div
        v-if="countdownEvent"
        :class="
          activeEvents.length
            ? 'order-first md:order-last md:w-1/3'
            : 'order-first md:w-full'
        "
        class="w-full print:hidden"
      >
        <div
          class="mb-4 rounded-lg border border-theme-200 bg-theme-50 p-4 dark:border-zinc-700 dark:bg-zinc-950"
        >
          <div class="flex items-center justify-between">
            <div>
              <div class="font-semibold text-theme-800 dark:text-zinc-200">
                {{ countdownEvent.name }}
              </div>
              <p
                class="mt-1 text-sm text-theme-700 tabular-nums dark:text-zinc-400"
              >
                {{ dateRange(countdownEvent) }}
              </p>
            </div>
            <div class="text-right">
              <div
                v-if="countdownEvent.status === 'ongoing'"
                class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800"
              >
                進行中
              </div>
              <div v-else>
                <div
                  class="text-3xl font-bold text-theme-700 dark:text-zinc-300"
                >
                  {{ countdownEvent.daysUntil }}
                </div>
                <div class="text-sm text-theme-700 dark:text-zinc-400">
                  天後
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!--
        Schedule Events (手機在 countdown 之下，桌面佔 2/3)
        當有 countdownEvent 時：不從列表移除該項目；在渲染時把該行於畫面上隱藏、僅於列印時顯示（避免畫面重複但列印可見）。
      -->
      <div
        v-if="activeEvents.length"
        :class="
          countdownEvent
            ? 'order-last md:order-first md:w-2/3'
            : 'order-first md:w-full'
        "
        class="w-full print:w-full"
      >
        <div class="space-y-2">
          <div
            v-for="event in activeEvents"
            :key="event.start + event.name"
            :class="isCountdownMatch(event) ? 'hidden print:flex' : 'flex'"
            class="flex-col-reverse items-start justify-between gap-x-2 gap-y-1 border-b border-theme-100 py-2 last:border-0 sm:flex-row sm:items-center dark:border-zinc-800"
          >
            <span class="font-medium text-theme-800 dark:text-zinc-200">
              {{ event.name }}
            </span>
            <div
              class="flex flex-col-reverse items-start gap-x-2 text-sm text-theme-700 tabular-nums sm:flex-row sm:items-center dark:text-zinc-400"
            >
              <span
                v-if="event.status === 'ongoing'"
                class="inline-flex shrink-0 items-center rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 print:hidden"
              >
                進行中
              </span>

              <span class="shrink-0">{{ shortDateRange(event) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <p
      v-if="showTaipeiHint"
      class="mt-3 text-xs text-theme-700 dark:text-zinc-400 print:hidden"
    >
      此區塊日期皆為台灣時間（Asia/Taipei）
    </p>
  </div>
</template>
