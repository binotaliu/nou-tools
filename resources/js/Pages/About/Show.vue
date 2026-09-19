<script setup>
// Purely static content (no ViewModel/props): the licensing notices that
// used to live in the footer.
import { Head } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
</script>

<template>
  <Head title="關於本站 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-8">
      <div>
        <h2
          class="text-3xl font-bold tracking-tight text-theme-700 dark:text-zinc-200"
        >
          關於本站
        </h2>
        <p class="mt-3 text-theme-600 dark:text-zinc-400">
          NOU 小幫手是給 NOU 同學的非官方小工具，由學生自發製作。
        </p>
      </div>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <h3 class="text-lg font-semibold text-theme-800 dark:text-zinc-100">
          開放原始碼授權聲明
        </h3>
        <p
          class="mt-3 text-justify text-sm leading-relaxed text-theme-700 dark:text-zinc-300"
        >
          本網站是自由且開放原始碼之軟體，使用 AGPL-3.0
          授權條款。歡迎各位同學自由審閱、修改、使用、再散佈本網站原始碼，但請遵守
          AGPL
          授權條款。如果您以任何形式參考了本網站之原始碼並開發了新的軟體，則此一沿伸軟體也必須使用與遵守
          AGPL 授權條款，請在閱讀、參考、引用本網站原始碼時特別注意授權問題。
        </p>
        <p class="mt-3 text-sm">
          <a
            href="https://github.com/binotaliu/nou-tools"
            class="text-theme-700 underline hover:text-theme-900 dark:text-zinc-300 dark:hover:text-zinc-100"
            target="_blank"
            rel="noopener noreferrer"
            >網站原始碼</a
          >
        </p>
      </section>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <h3 class="text-lg font-semibold text-theme-800 dark:text-zinc-100">
          圖樣授權聲明
        </h3>
        <p
          class="mt-3 text-justify text-sm leading-relaxed text-theme-700 dark:text-zinc-300"
        >
          分享卡片背景圖樣「I Like Food」與「Plus」出自 Steve Schoger 的
          <a
            href="https://heropatterns.com/"
            class="underline hover:text-theme-900 dark:hover:text-zinc-100"
            target="_blank"
            rel="noopener noreferrer"
            >Hero Patterns</a
          >，依
          <a
            href="https://creativecommons.org/licenses/by/4.0/"
            class="underline hover:text-theme-900 dark:hover:text-zinc-100"
            target="_blank"
            rel="noopener noreferrer"
            >CC BY 4.0</a
          >
          授權使用，本站已調整其顏色與透明度。
        </p>
      </section>
    </div>
  </AppLayout>
</template>
