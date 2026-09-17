<script setup>
// See useGreeting.js for the greeting logic.
import useGreeting from '../Composables/useGreeting'

const props = defineProps({
  semesterLabel: {
    type: String,
    required: true,
  },
  semesterCode: {
    type: String,
    required: true,
  },
  semesterStart: {
    type: String,
    default: null,
  },
  semesterEnd: {
    type: String,
    default: null,
  },
})

const {
  greetingText,
  dateString,
  semesterInfo,
  compactMode,
  compactDateString,
  compactSemesterInfo,
  showTaiwanClock,
  taiwanHour,
  taiwanMinute,
  taiwanDateString,
  toggleCompact,
} = useGreeting({
  semesterLabel: props.semesterLabel,
  semesterCode: props.semesterCode,
  semesterStart: props.semesterStart,
  semesterEnd: props.semesterEnd,
})
</script>

<template>
  <div
    data-testid="greeting-widget"
    class="cursor-pointer select-none print:hidden"
    @click="toggleCompact()"
  >
    <div
      class="rounded-lg border border-theme-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
      :class="compactMode ? 'px-4 py-2' : 'p-6'"
    >
      <div
        v-if="!compactMode"
        data-testid="greeting-normal"
        class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
      >
        <div class="flex flex-col justify-between gap-1">
          <p class="text-xl font-semibold sm:text-2xl md:text-3xl">
            <span>{{ greetingText }}</span>
            ，歡迎回來！
          </p>

          <p class="text-theme-500 dark:text-zinc-400">
            今天是
            <span>{{ dateString }}</span>
            ，
            <span>{{ semesterInfo }}</span>
          </p>
        </div>

        <div
          v-if="showTaiwanClock"
          data-testid="taiwan-clock"
          class="flex shrink-0 flex-row items-center justify-between gap-3 border-t border-theme-200 pt-3 sm:flex-col sm:items-end sm:justify-start sm:border-t-0 sm:border-l sm:pt-0 sm:pl-4 dark:border-zinc-700"
        >
          <div
            class="inline-flex items-center text-2xl font-semibold text-theme-700 tabular-nums sm:text-3xl dark:text-zinc-300"
          >
            <span>{{ taiwanHour }}</span>
            <span class="blink-colon">:</span>
            <span>{{ taiwanMinute }}</span>
          </div>
          <div class="text-center">
            <p class="text-xs text-theme-500 tabular-nums dark:text-zinc-400">
              {{ taiwanDateString }}
            </p>
            <p class="text-[0.65rem] text-theme-400 dark:text-zinc-500">
              台灣時間
            </p>
          </div>
        </div>
      </div>

      <div
        v-else
        data-testid="greeting-compact"
        class="flex flex-row items-center justify-between gap-3 text-sm text-theme-500 tabular-nums dark:text-zinc-400"
      >
        <span>
          <span>{{ compactDateString }}</span>
          ・
          <span>{{ compactSemesterInfo }}</span>
        </span>

        <span v-show="showTaiwanClock" data-testid="taiwan-clock-compact">
          台灣時間: <span>{{ taiwanHour }}</span> :
          <span>{{ taiwanMinute }}</span>
        </span>
      </div>
    </div>
  </div>
</template>
