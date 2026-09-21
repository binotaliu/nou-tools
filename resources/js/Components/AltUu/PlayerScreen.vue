<script setup>
import { useId } from 'vue'
import Icon from '../Icon.vue'

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

  <!-- A lecture slide (title, key points, a balance-scale diagram) with the player bar underneath -->
  <svg
    viewBox="0 0 224 126"
    class="mx-2 mt-2.5 aspect-video w-[calc(100%-1rem)] rounded-lg"
    role="presentation"
  >
    <defs>
      <clipPath :id="clipId">
        <rect width="224" height="126" rx="8" />
      </clipPath>
    </defs>

    <g :clip-path="`url(#${clipId})`">
      <!-- Slide -->
      <rect width="224" height="126" class="fill-white dark:fill-zinc-900" />
      <rect width="224" height="4" class="fill-theme-500" />

      <rect
        x="14"
        y="13"
        width="34"
        height="12"
        rx="6"
        class="fill-theme-100 dark:fill-theme-900"
      />
      <text
        x="31"
        y="21.5"
        font-size="6.5"
        text-anchor="middle"
        class="fill-theme-700 dark:fill-theme-300"
      >
        第 08 講
      </text>

      <text
        x="14"
        y="41"
        font-size="11"
        font-weight="700"
        class="fill-theme-900 dark:fill-zinc-100"
      >
        日常生活中的性別偏見
      </text>
      <rect x="14" y="47" width="22" height="2" rx="1" class="fill-theme-500" />

      <!-- Key points -->
      <g class="fill-theme-800 dark:fill-zinc-300" font-size="7.5">
        <circle cx="16" cy="61" r="2" class="fill-theme-500" />
        <text x="24" y="63.5">刻板印象與角色期待</text>
        <circle cx="16" cy="74" r="2" class="fill-theme-500" />
        <text x="24" y="76.5">職場中的隱性偏見</text>
        <circle cx="16" cy="87" r="2" class="fill-theme-500" />
        <text x="24" y="89.5">教育現場的性別平等</text>
      </g>

      <!-- Balance scale -->
      <g
        fill="none"
        stroke-width="1.5"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="stroke-theme-500"
      >
        <path d="M176 40v40M150 40h52" />
        <path d="M150 40l-9 22M150 40l9 22M202 40l-9 22M202 40l9 22" />
        <path d="M139 62h22a11 11 0 0 1-22 0ZM191 62h22a11 11 0 0 1-22 0Z" />
      </g>
      <circle cx="176" cy="38" r="3" class="fill-theme-500" />
      <rect
        x="165"
        y="80"
        width="22"
        height="4"
        rx="2"
        class="fill-theme-500"
      />
      <circle cx="150" cy="56" r="5" class="fill-rose-400" />
      <circle cx="202" cy="56" r="5" class="fill-sky-400" />

      <!-- Player bar -->
      <rect y="100" width="224" height="26" class="fill-zinc-900/85" />
      <path d="M10 106v14l11-7Z" class="fill-white" />
      <text x="28" y="116" font-size="6.5" class="fill-white">04:53</text>
      <rect
        x="54"
        y="112"
        width="136"
        height="3"
        rx="1.5"
        class="fill-white/30"
      />
      <rect x="54" y="112" width="20" height="3" rx="1.5" class="fill-white" />
      <circle cx="74" cy="113.5" r="3.5" class="fill-white" />
      <text
        x="216"
        y="116"
        font-size="6.5"
        text-anchor="end"
        class="fill-white"
      >
        33:14
      </text>
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
