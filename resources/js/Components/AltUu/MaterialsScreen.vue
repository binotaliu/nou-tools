<script setup>
import Icon from '../Icon.vue'

const tabs = [
  { label: '教材', active: true },
  { label: '討論', badge: 14 },
  { label: '作業' },
  { label: '練習' },
]

const lectures = [
  { title: '課程介紹', time: '00:20' },
  { title: '第 01 講 - 性別平權的基本概念與歷史發展', time: '37:26' },
  { title: '第 02 講 - 性別、性別認同與性傾向的區別', time: '29:53' },
  { title: '第 03 講 - 性別刻板印象的形成與影響', time: '30:00' },
  { title: '第 04 講 - 家庭、學校與媒體中的性別再現', time: '30:17' },
  { title: '第 05 講 - 教育體制中的性別不平等', time: '34:10' },
]
</script>

<template>
  <div class="mt-3 flex items-start gap-1.5 px-2.5">
    <Icon name="chevron-left" class="mt-0.5 size-3 text-theme-700" />
    <div>
      <p class="text-[10px] font-semibold text-theme-900 dark:text-zinc-100">
        性別平等與教育
      </p>
      <p class="mt-0.5 flex items-center gap-1 text-[8px]">
        <span class="text-theme-700 dark:text-zinc-400">114下</span>
        <span class="flex overflow-hidden rounded-full text-[7px]">
          <span
            class="bg-theme-100 px-1.5 py-px text-theme-700 dark:bg-zinc-800 dark:text-zinc-300"
          >
            影音
          </span>
          <span
            class="bg-zinc-100 px-1.5 py-px font-mono text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
          >
            ZZZ001班
          </span>
        </span>
      </p>
    </div>
  </div>

  <div
    class="mx-2 mt-2 flex items-center rounded-lg border border-theme-200 bg-white p-0.5 text-[8px] dark:border-zinc-800 dark:bg-zinc-900"
  >
    <span
      v-for="tab in tabs"
      :key="tab.label"
      class="flex flex-1 items-center justify-center gap-0.5 rounded-md py-1"
      :class="
        tab.active
          ? 'bg-theme-800 font-semibold text-white dark:bg-theme-700'
          : 'text-theme-700 dark:text-zinc-400'
      "
    >
      {{ tab.label }}
      <span
        v-if="tab.badge"
        class="rounded-full bg-theme-800 px-1 text-[6.5px] text-white dark:bg-theme-700"
      >
        {{ tab.badge }}
      </span>
    </span>
  </div>

  <div
    class="mx-2 mt-2 flex-1 overflow-hidden rounded-lg border border-theme-200 bg-white dark:border-zinc-800 dark:bg-zinc-900"
  >
    <div
      class="flex items-center justify-between border-b border-theme-100 px-2 py-1.5 dark:border-zinc-800"
    >
      <span
        class="flex items-center gap-1 text-[9px] font-semibold text-theme-900 dark:text-zinc-100"
      >
        <Icon name="book-open" class="size-3" />
        教材目錄
      </span>
      <span class="flex gap-1 text-[6.5px] text-theme-700 dark:text-zinc-400">
        <span
          class="rounded-full border border-theme-200 px-1.5 py-px dark:border-zinc-700"
        >
          全部展開
        </span>
        <span
          class="rounded-full border border-theme-200 px-1.5 py-px dark:border-zinc-700"
        >
          全部收合
        </span>
      </span>
    </div>

    <div class="space-y-1 p-1.5">
      <div
        v-for="lecture in lectures"
        :key="lecture.title"
        class="flex items-center justify-between gap-1 rounded-md border border-theme-100 px-1.5 py-1.5 dark:border-zinc-800"
      >
        <span class="truncate text-[7.5px] text-theme-800 dark:text-zinc-300">
          {{ lecture.title }}
        </span>
        <span
          class="flex shrink-0 items-center gap-0.5 rounded-full bg-emerald-500/15 px-1.5 py-px text-[7px] font-medium text-emerald-800 dark:text-emerald-400"
        >
          <Icon name="clock" class="size-2" />
          {{ lecture.time }}
        </span>
      </div>
    </div>
  </div>
</template>
