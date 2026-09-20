<script setup>
// Decorative hero art for 自習室: a night-time study room whose desks are
// filled with classmates, one of them "you".
import { useId } from 'vue'

const clipId = useId()

const seatX = [62, 127, 193, 258]

// Row index → column indexes that are taken; [1, 1] is the viewer's own seat.
const rows = [
  { headY: 130, taken: [0, 1, 3] },
  { headY: 178, taken: [1, 2] },
]

const stars = [
  [64, 52],
  [96, 72],
  [150, 48],
  [186, 76],
  [214, 50],
  [88, 40],
]
</script>

<template>
  <svg viewBox="0 0 320 240" aria-hidden="true" class="h-auto w-full">
    <defs>
      <clipPath :id="clipId">
        <rect x="20" y="22" width="280" height="200" rx="16" />
      </clipPath>
    </defs>

    <g :clip-path="`url(#${clipId})`">
      <rect
        x="20"
        y="22"
        width="280"
        height="200"
        class="fill-theme-800 dark:fill-zinc-800"
      />
      <rect
        x="20"
        y="112"
        width="280"
        height="110"
        class="fill-theme-700 dark:fill-zinc-700"
      />

      <rect
        x="40"
        y="38"
        width="240"
        height="62"
        rx="8"
        class="fill-theme-900 stroke-theme-600 dark:fill-zinc-950 dark:stroke-zinc-600"
        stroke-width="3"
      />
      <circle
        v-for="[x, y] in stars"
        :key="`${x}-${y}`"
        :cx="x"
        :cy="y"
        r="1.8"
        fill="white"
        opacity="0.85"
      />
      <circle cx="248" cy="62" r="15" class="fill-theme-100" />
      <circle
        cx="255"
        cy="57"
        r="13"
        class="fill-theme-900 dark:fill-zinc-950"
      />

      <template v-for="(row, r) in rows" :key="r">
        <template v-for="(x, c) in seatX" :key="c">
          <template v-if="row.taken.includes(c)">
            <circle
              :cx="x"
              :cy="row.headY"
              r="9"
              :class="
                r === 0 && c === 1
                  ? 'fill-amber-300'
                  : (r + c) % 2
                    ? 'fill-theme-300'
                    : 'fill-theme-200'
              "
            />
            <path
              :d="`M${x - 16} ${row.headY + 22}q0-14 16-14t16 14z`"
              :class="
                r === 0 && c === 1
                  ? 'fill-amber-400'
                  : (r + c) % 2
                    ? 'fill-theme-400'
                    : 'fill-theme-300'
              "
            />
          </template>
          <circle
            v-else
            :cx="x"
            :cy="row.headY"
            r="9"
            fill="none"
            stroke="white"
            stroke-width="1.5"
            stroke-dasharray="3 3"
            opacity="0.45"
          />
          <rect
            :x="x - 24"
            :y="row.headY + 22"
            width="48"
            height="9"
            rx="4"
            class="fill-theme-100 dark:fill-zinc-300"
          />
          <rect
            v-if="row.taken.includes(c)"
            :x="x - 10"
            :y="row.headY + 15"
            width="20"
            height="7"
            rx="2"
            class="fill-white"
            opacity="0.85"
          />
        </template>
      </template>

      <circle
        cx="127"
        cy="130"
        r="16"
        fill="none"
        class="stroke-amber-300"
        stroke-width="2"
        stroke-dasharray="4 3"
      />
    </g>

    <g>
      <rect
        x="99"
        y="98"
        width="66"
        height="22"
        rx="11"
        class="fill-white stroke-theme-500 dark:fill-zinc-900"
        stroke-width="2"
      />
      <circle cx="113" cy="109" r="4" class="fill-emerald-500" />
      <text
        x="123"
        y="113"
        font-size="11"
        class="fill-theme-900 font-semibold dark:fill-zinc-100"
      >
        專注中
      </text>
    </g>
  </svg>
</template>
