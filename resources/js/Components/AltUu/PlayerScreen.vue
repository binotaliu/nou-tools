<script setup>
import { useId } from 'vue'
import Icon from '../Icon.vue'

const scrimId = useId()
const clipId = useId()
</script>

<template>
  <div class="mt-3 flex items-start justify-between gap-1 px-2.5">
    <div class="flex items-start gap-1.5">
      <Icon name="chevron-left" class="mt-0.5 size-3 text-theme-700" />
      <div>
        <p class="text-[10px] font-semibold text-theme-900 dark:text-zinc-100">
          性別平權與教育
        </p>
        <p class="mt-0.5 text-[7.5px] text-theme-700 dark:text-zinc-400">
          第 08 講 - 日常生活中的性別偏見…
        </p>
      </div>
    </div>
    <span
      class="flex shrink-0 items-center gap-0.5 rounded-full bg-theme-100 px-1.5 py-0.5 text-[7.5px] text-theme-800 dark:bg-zinc-800 dark:text-zinc-300"
    >
      <Icon name="clock" class="size-2.5" />
      04:53
    </span>
  </div>

  <!-- A lecture video still: studio backdrop, lecturer and player scrubber -->
  <svg
    viewBox="0 0 224 126"
    class="mx-2 mt-2.5 aspect-video w-[calc(100%-1rem)] rounded-lg"
    role="presentation"
  >
    <defs>
      <linearGradient :id="scrimId" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#000" stop-opacity="0" />
        <stop offset="1" stop-color="#000" stop-opacity="0.45" />
      </linearGradient>
      <clipPath :id="clipId">
        <rect width="224" height="126" rx="8" />
      </clipPath>
    </defs>

    <g :clip-path="`url(#${clipId})`">
      <!-- Backdrop -->
      <rect
        width="224"
        height="126"
        class="fill-theme-100 dark:fill-theme-900"
      />
      <path
        d="M14 126V72a32 32 0 0 1 64 0v54Z"
        class="fill-theme-200 dark:fill-theme-800"
      />
      <circle cx="44" cy="32" r="16" class="fill-amber-300/80" />
      <ellipse
        cx="26"
        cy="118"
        rx="38"
        ry="22"
        class="fill-sky-300/60 dark:fill-sky-700/50"
      />

      <!-- Shelf, vase and plant -->
      <rect
        x="168"
        width="56"
        height="126"
        class="fill-theme-200 dark:fill-theme-800"
      />
      <rect
        x="178"
        y="14"
        width="32"
        height="22"
        rx="2"
        class="fill-white stroke-theme-300 dark:fill-zinc-900 dark:stroke-theme-700"
      />
      <path
        d="M182 32l8-9 6 6 5-4 9 7Z"
        class="fill-theme-300 dark:fill-theme-700"
      />
      <rect
        x="170"
        y="86"
        width="54"
        height="3"
        class="fill-theme-400 dark:fill-theme-600"
      />
      <path
        d="M186 86c-8 0-9-10-4-16l2-2h8l2 2c5 6 4 16-4 16Z"
        class="fill-orange-300"
      />
      <rect x="186" y="60" width="4" height="9" class="fill-orange-300" />
      <path
        d="M210 86V54"
        class="stroke-emerald-600/70 dark:stroke-emerald-400/70"
        stroke-width="1.5"
        stroke-linecap="round"
      />
      <ellipse
        cx="205"
        cy="60"
        rx="3.5"
        ry="8"
        transform="rotate(-35 205 60)"
        class="fill-emerald-500/70"
      />
      <ellipse
        cx="215"
        cy="66"
        rx="3.5"
        ry="8"
        transform="rotate(35 215 66)"
        class="fill-emerald-500/70"
      />
      <ellipse cx="210" cy="52" rx="3" ry="7" class="fill-emerald-500/70" />

      <!-- Lecturer -->
      <path
        d="M62 126c2-26 20-34 50-34s48 8 50 34Z"
        class="fill-stone-200 dark:fill-stone-300"
      />
      <path d="M97 93l15 26 15-26Z" class="fill-white" />
      <path
        d="M97 93l11 33M127 93l-11 33"
        class="stroke-stone-300 dark:stroke-stone-400"
        stroke-width="1.5"
        stroke-linecap="round"
      />
      <rect
        x="104"
        y="78"
        width="16"
        height="18"
        rx="6"
        class="fill-orange-200"
      />
      <ellipse cx="112" cy="63" rx="17" ry="20" class="fill-orange-200" />
      <path
        d="M90 76c-5-32 9-46 24-46 20 0 26 20 20 46-2-16-8-27-22-28-12 1-19 12-22 28Z"
        class="fill-zinc-800"
      />
      <circle cx="105" cy="66" r="1.5" class="fill-zinc-800" />
      <circle cx="119" cy="66" r="1.5" class="fill-zinc-800" />
      <path
        d="M108 75q4 3 8 0"
        class="stroke-rose-400"
        fill="none"
        stroke-width="1.5"
        stroke-linecap="round"
      />
      <rect
        x="126"
        y="100"
        width="2.5"
        height="6"
        rx="1"
        class="fill-zinc-700"
      />
      <path
        d="M152 126c2-14-3-22-12-25"
        class="stroke-stone-200 dark:stroke-stone-300"
        fill="none"
        stroke-width="13"
        stroke-linecap="round"
      />
      <ellipse
        cx="137"
        cy="99"
        rx="6.5"
        ry="5.5"
        transform="rotate(-25 137 99)"
        class="fill-orange-200"
      />

      <!-- Player scrubber -->
      <rect y="84" width="224" height="42" :fill="`url(#${scrimId})`" />
      <text x="8" y="112" font-size="7" class="fill-white">04:53</text>
      <text x="216" y="112" font-size="7" text-anchor="end" class="fill-white">
        33:14
      </text>
      <rect
        x="8"
        y="117"
        width="208"
        height="3"
        rx="1.5"
        class="fill-white/40"
      />
      <rect x="8" y="117" width="62" height="3" rx="1.5" class="fill-white" />
      <circle cx="70" cy="118.5" r="4" class="fill-white" />
    </g>
  </svg>

  <div
    class="mx-2 mt-2 flex items-center gap-1 rounded-lg border border-theme-200 bg-white p-1 text-[7.5px] dark:border-zinc-800 dark:bg-zinc-900"
  >
    <span
      class="flex-1 rounded-md border border-theme-200 py-1 text-center text-theme-800 dark:border-zinc-700 dark:text-zinc-300"
    >
      使用內建瀏覽器開啟
    </span>
    <span
      class="flex items-center gap-1.5 rounded-md border border-theme-200 px-1.5 py-1 text-theme-800 dark:border-zinc-700 dark:text-zinc-300"
    >
      A-
      <span class="font-semibold">100%</span>
      A+
    </span>
  </div>

  <div
    class="mx-2 mt-2 flex-1 space-y-1.5 overflow-hidden rounded-lg border border-theme-200 bg-white p-2.5 dark:border-zinc-800 dark:bg-zinc-900"
  >
    <span
      v-for="width in ['w-full', 'w-full', 'w-11/12', 'w-4/5']"
      :key="width"
      class="block h-1 rounded-full bg-theme-200 dark:bg-zinc-700"
      :class="width"
    ></span>
    <span class="block h-2"></span>
    <span
      v-for="width in ['w-full', 'w-10/12', 'w-full', 'w-2/3']"
      :key="width + 'b'"
      class="block h-1 rounded-full bg-theme-200 dark:bg-zinc-700"
      :class="width"
    ></span>
  </div>
</template>
