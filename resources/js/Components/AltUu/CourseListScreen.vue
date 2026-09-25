<script setup>
import Icon from '../Icon.vue'

const courses = [
  { kind: '語音', title: '網路行銷輕鬆學', materials: 2, discussions: 6 },
  { kind: '網頁', title: '通用設計導論', materials: 1, discussions: 3 },
  { kind: '影音', title: '性別平權與教育', materials: 0, discussions: 14 },
  { kind: '語音', title: '世界語入門', materials: 0, discussions: 5 },
  { kind: '網頁', title: '如何幫一隻浣熊洗澡', materials: 2, discussions: 36 },
]
</script>

<template>
  <div class="mt-3 flex items-center justify-between px-3">
    <div
      class="flex items-center gap-1 text-[11px] font-semibold text-theme-900 dark:text-zinc-100"
    >
      <Icon name="academic-cap" class="size-3.5" />
      我的課程
    </div>
    <span
      class="rounded-full border border-theme-200 bg-white px-2 py-0.5 text-[8px] text-theme-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300"
    >
      設定
    </span>
  </div>

  <p
    class="mt-3 px-3.5 text-[9px] font-semibold text-theme-700 dark:text-zinc-400"
  >
    114下
  </p>

  <div class="mt-1.5 flex-1 space-y-1.5 overflow-hidden px-2.5">
    <div
      v-for="course in courses"
      :key="course.title"
      class="rounded-lg border border-theme-200 bg-white px-2 py-1.5 dark:border-zinc-800 dark:bg-zinc-900"
    >
      <div class="flex items-center justify-between gap-1">
        <span class="flex overflow-hidden rounded-full text-[7px]">
          <span
            class="bg-theme-100 px-1.5 py-px text-theme-700 dark:bg-zinc-800 dark:text-zinc-300"
          >
            {{ course.kind }}
          </span>
          <span
            class="bg-zinc-100 px-1.5 py-px font-mono text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
          >
            ZZZ001班
          </span>
        </span>
        <span class="flex gap-1 text-[7px] font-medium">
          <span
            v-if="course.materials"
            class="rounded-full border border-red-700/70 px-1.5 text-red-700 dark:border-red-400/70 dark:text-red-400"
          >
            {{ course.materials }}
          </span>
          <span
            class="rounded-full border border-amber-800/70 px-1.5 text-amber-800 dark:border-amber-400/70 dark:text-amber-400"
          >
            {{ course.discussions }}
          </span>
        </span>
      </div>
      <p
        class="mt-1.5 mb-2 text-[9px] font-semibold text-theme-900 dark:text-zinc-100"
      >
        {{ course.title }}
      </p>
    </div>
  </div>
</template>
