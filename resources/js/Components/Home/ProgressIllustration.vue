<script setup>
// Decorative hero art for 學習進度表: per-course progress bars beside an
// overall completion ring.
const radius = 42
const circumference = 2 * Math.PI * radius
const completed = 0.68

// Label bar width, filled share of the track, and whether the course is done.
const rows = [
  { label: 64, filled: 1, done: true },
  { label: 48, filled: 0.66, done: false },
  { label: 58, filled: 0.34, done: false },
]
</script>

<template>
  <svg viewBox="0 0 320 240" aria-hidden="true" class="h-auto w-full">
    <rect
      x="24"
      y="34"
      width="196"
      height="172"
      rx="14"
      class="fill-white stroke-theme-200 dark:fill-zinc-800 dark:stroke-zinc-600"
      stroke-width="2"
    />

    <g v-for="(row, i) in rows" :key="i">
      <template v-if="row.done">
        <circle
          cx="48"
          :cy="66 + i * 46"
          r="9"
          class="fill-theme-600 dark:fill-theme-500"
        />
        <path
          :d="`M43.5 ${66 + i * 46}l3.2 3.2 5.8-6.4`"
          fill="none"
          stroke="white"
          stroke-width="2.2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </template>
      <circle
        v-else
        cx="48"
        :cy="66 + i * 46"
        r="8"
        fill="none"
        class="stroke-theme-300 dark:stroke-zinc-500"
        stroke-width="2"
      />
      <rect
        x="64"
        :y="62 + i * 46"
        :width="row.label"
        height="8"
        rx="4"
        class="fill-theme-800 dark:fill-zinc-200"
      />
      <rect
        x="42"
        :y="82 + i * 46"
        width="136"
        height="10"
        rx="5"
        class="fill-theme-100 dark:fill-zinc-700"
      />
      <rect
        x="42"
        :y="82 + i * 46"
        :width="136 * row.filled"
        height="10"
        rx="5"
        class="fill-theme-500 dark:fill-theme-500"
      />
    </g>

    <circle
      cx="236"
      cy="120"
      r="58"
      class="fill-white stroke-theme-200 dark:fill-zinc-900 dark:stroke-zinc-600"
      stroke-width="2"
    />
    <g transform="rotate(-90 236 120)">
      <circle
        cx="236"
        cy="120"
        :r="radius"
        fill="none"
        class="stroke-theme-100 dark:stroke-zinc-700"
        stroke-width="12"
      />
      <circle
        cx="236"
        cy="120"
        :r="radius"
        fill="none"
        class="stroke-theme-600 dark:stroke-theme-500"
        stroke-width="12"
        stroke-linecap="round"
        :stroke-dasharray="`${circumference * completed} ${circumference}`"
      />
    </g>
    <text
      x="236"
      y="123"
      text-anchor="middle"
      font-size="26"
      class="fill-theme-900 font-bold dark:fill-zinc-100"
    >
      68%
    </text>
    <text
      x="236"
      y="140"
      text-anchor="middle"
      font-size="11"
      class="fill-theme-600 dark:fill-zinc-400"
    >
      總進度
    </text>

    <path
      d="M282 60l3 7 7 3-7 3-3 7-3-7-7-3 7-3z"
      class="fill-theme-400 dark:fill-theme-500"
    />
    <path
      d="M204 196l2 5 5 2-5 2-2 5-2-5-5-2 5-2z"
      class="fill-theme-300 dark:fill-theme-700"
    />
  </svg>
</template>
