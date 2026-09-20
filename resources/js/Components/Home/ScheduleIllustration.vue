<script setup>
// Decorative hero art for 課表管理: a weekly timetable with the next
// in-person session called out. Colours come from the theme tokens so it
// follows the visitor's accent colour and dark mode.
const days = ['一', '二', '三', '四', '五']

// Column/row pairs of the cells that hold a class; [2, 1] is the next one.
const busy = [
  [0, 0],
  [1, 2],
  [3, 0],
  [3, 1],
  [4, 2],
]
</script>

<template>
  <svg viewBox="0 0 320 240" aria-hidden="true" class="h-auto w-full">
    <rect
      x="30"
      y="30"
      width="220"
      height="172"
      rx="14"
      class="fill-white stroke-theme-200 dark:fill-zinc-800 dark:stroke-zinc-600"
      stroke-width="2"
    />
    <rect
      x="30"
      y="30"
      width="220"
      height="34"
      rx="14"
      class="fill-theme-600 dark:fill-theme-700"
    />
    <rect
      x="30"
      y="48"
      width="220"
      height="16"
      class="fill-theme-600 dark:fill-theme-700"
    />
    <rect
      x="70"
      y="20"
      width="8"
      height="20"
      rx="4"
      class="fill-theme-900 dark:fill-zinc-300"
    />
    <rect
      x="202"
      y="20"
      width="8"
      height="20"
      rx="4"
      class="fill-theme-900 dark:fill-zinc-300"
    />

    <text
      v-for="(day, i) in days"
      :key="day"
      :x="62 + i * 40"
      y="84"
      text-anchor="middle"
      font-size="11"
      class="fill-theme-700 font-semibold dark:fill-zinc-300"
    >
      {{ day }}
    </text>

    <template v-for="row in 3" :key="row">
      <rect
        v-for="(day, col) in days"
        :key="`${row}-${day}`"
        :x="46 + col * 40"
        :y="94 + (row - 1) * 34"
        width="32"
        height="28"
        rx="6"
        class="fill-theme-50 dark:fill-zinc-700/60"
      />
    </template>

    <rect
      v-for="[col, row] in busy"
      :key="`busy-${col}-${row}`"
      :x="46 + col * 40"
      :y="94 + row * 34"
      width="32"
      height="28"
      rx="6"
      class="fill-theme-300 dark:fill-theme-800"
    />

    <rect
      x="126"
      y="128"
      width="32"
      height="28"
      rx="6"
      class="fill-theme-600 stroke-theme-900 dark:fill-theme-500 dark:stroke-zinc-100"
      stroke-width="2"
    />

    <g>
      <rect
        x="164"
        y="146"
        width="140"
        height="58"
        rx="12"
        class="fill-white stroke-theme-500 dark:fill-zinc-900 dark:stroke-theme-500"
        stroke-width="2"
      />
      <circle
        cx="188"
        cy="175"
        r="13"
        class="fill-theme-100 dark:fill-zinc-700"
      />
      <circle
        cx="188"
        cy="175"
        r="8"
        fill="none"
        class="stroke-theme-700 dark:stroke-theme-400"
        stroke-width="2"
      />
      <path
        d="M188 170v5l3 2"
        fill="none"
        class="stroke-theme-700 dark:stroke-theme-400"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      />
      <text
        x="208"
        y="171"
        font-size="11"
        class="fill-theme-600 dark:fill-zinc-400"
      >
        下次面授
      </text>
      <text
        x="208"
        y="190"
        font-size="16"
        class="fill-theme-900 font-bold dark:fill-zinc-100"
      >
        週三 19:00
      </text>
    </g>
  </svg>
</template>
