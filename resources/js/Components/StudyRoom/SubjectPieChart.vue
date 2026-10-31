<script setup>
// Donut of each subject's share of the period's focus time. d3-shape only
// computes the arc paths; Vue renders plain SVG, so colours follow Tailwind
// classes in light and dark. The legend carries the same numbers as text, so
// the SVG itself is a labelled image rather than the only source.
import { computed } from 'vue'
import { arc, pie } from 'd3-shape'
import { formatDurationLabel } from '../../study-room-format'

const props = defineProps({
  subjects: { type: Array, required: true },
  testid: { type: String, default: 'study-room-subject-pie' },
})

// Classes in slice order (literal, so Tailwind sees them); the legend swatches reuse them.
const COLORS = [
  {
    fill: 'fill-theme-600 dark:fill-theme-400',
    bg: 'bg-theme-600 dark:bg-theme-400',
  },
  { fill: 'fill-amber-500', bg: 'bg-amber-500' },
  { fill: 'fill-emerald-500', bg: 'bg-emerald-500' },
  { fill: 'fill-rose-500', bg: 'bg-rose-500' },
  { fill: 'fill-sky-500', bg: 'bg-sky-500' },
  { fill: 'fill-zinc-400', bg: 'bg-zinc-400' },
]

const RADIUS = 50
const generator = arc().innerRadius(28).outerRadius(RADIUS).padAngle(0.02)

const slices = computed(() =>
  pie()
    .sort(null)
    .value(subject => subject.focusSeconds)(props.subjects)
    .map(slice => ({
      path: generator(slice),
      subject: slice.data,
      color: COLORS[slice.index % COLORS.length],
    }))
)

const summary = computed(() =>
  props.subjects
    .map(subject => subject.label + ' ' + subject.percent + '%')
    .join('、')
)
</script>

<template>
  <div :data-testid="testid">
    <p
      v-if="subjects.length === 0"
      class="text-sm text-theme-700 dark:text-zinc-400"
    >
      還沒有紀錄
    </p>

    <div v-else class="flex flex-wrap items-center gap-4">
      <svg
        viewBox="-50 -50 100 100"
        class="size-32 shrink-0"
        role="img"
        :aria-label="'科目分佈：' + summary"
      >
        <path
          v-for="slice in slices"
          :key="slice.subject.label"
          :d="slice.path"
          :class="slice.color.fill"
        />
      </svg>

      <ul class="min-w-0 flex-1 space-y-1.5 text-sm">
        <li
          v-for="slice in slices"
          :key="slice.subject.label"
          class="flex items-center gap-2"
        >
          <span
            class="size-3 shrink-0 rounded-sm"
            :class="slice.color.bg"
            aria-hidden="true"
          ></span>
          <span class="truncate text-theme-800 dark:text-zinc-200">{{
            slice.subject.label
          }}</span>
          <span class="ml-auto shrink-0 text-theme-700 dark:text-zinc-400"
            >{{ formatDurationLabel(slice.subject.focusSeconds) }} ·
            {{ slice.subject.percent }}%</span
          >
        </li>
      </ul>
    </div>
  </div>
</template>
