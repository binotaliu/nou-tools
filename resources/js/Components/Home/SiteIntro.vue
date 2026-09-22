<script setup>
import Icon from '../Icon.vue'

const points = [
  {
    icon: 'lock-closed',
    title: '無需註冊',
    description:
      '使用 NOU 小幫手不需註冊，也不需要登入，即可使用本站所有功能。',
  },
  {
    icon: 'globe-alt',
    title: '不需安裝 App',
    description:
      'NOU 小幫手是純網頁的小工具，只需要在手機或電腦上打開網頁即可使用。',
  },
  {
    icon: 'table-cells',
    title: '管理課表與學習進度',
    description:
      '建立課表後，就可看到下次面授時間、使用學習進度表等功能，讓你的學習更有效率。',
  },
  {
    icon: 'megaphone',
    title: '更多實用資訊',
    description:
      'NOU 小幫手彙整了學校公告與行事曆等資訊，還幫你整理了空大學生可用的學生優惠，讓你不錯過任何好康。',
  },
]
</script>

<template>
  <div
    class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 print:hidden"
    data-testid="home-site-intro"
  >
    <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
      NOU 小幫手是什麼？
    </h2>
    <p class="mt-2 text-sm text-theme-600 dark:text-zinc-400">
      NOU
      小幫手是給空大同學的非官方小工具，提供面授課表、學習進度管理、自習室等功能。免登入、免安裝，打開網頁即可立即使用。
    </p>

    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div
        v-for="point in points"
        :key="point.title"
        class="flex items-start gap-3"
      >
        <div
          class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-theme-50 text-theme-700 dark:bg-zinc-800 dark:text-zinc-300"
        >
          <Icon :name="point.icon" class="size-5" aria-hidden="true" />
        </div>
        <div>
          <p class="text-sm font-semibold text-theme-900 dark:text-zinc-100">
            {{ point.title }}
          </p>
          <p class="mt-0.5 text-sm text-theme-600 dark:text-zinc-400">
            {{ point.description }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
