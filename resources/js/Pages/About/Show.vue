<script setup>
// Purely static content (no ViewModel/props): the licensing notices that
// used to live in the footer.
import { Head } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
</script>

<template>
  <Head title="關於本站 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-8">
      <div>
        <div
          class="flex flex-col items-center gap-1 md:flex-row md:gap-4"
          data-testid="about-brand"
        >
          <div class="p-3">
            <Icon
              name="book-open"
              class="size-6 text-theme-700 dark:text-zinc-300"
            />
          </div>
          <div class="text-center md:text-left">
            <p class="text-lg font-semibold text-theme-700 dark:text-zinc-300">
              NOU 小幫手
            </p>
            <p class="mt-1 text-xs text-theme-500 dark:text-zinc-400">
              給 NOU 同學的非官方小工具
            </p>
          </div>
        </div>
        <h2
          class="mt-6 text-3xl font-bold tracking-tight text-theme-700 dark:text-zinc-200 bottom-nav:hidden"
          data-testid="about-title"
        >
          關於本站
        </h2>
        <p
          class="mt-3 text-theme-600 dark:text-zinc-400 bottom-nav:hidden"
          data-testid="about-subtitle"
        >
          NOU 小幫手是給 NOU 同學的非官方小工具，由學生自發製作。
        </p>
      </div>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="about-disclaimer"
      >
        <h3 class="text-lg font-semibold text-theme-800 dark:text-zinc-100">
          免責聲明
        </h3>
        <p
          class="mt-3 text-justify text-sm leading-relaxed text-theme-700 dark:text-zinc-300"
        >
          本網站為學生自發製作之工具，僅供同學參考使用，並非學校官方發布；所有資訊以學校正式公告為準；本網站已盡可能提供準確資訊，但不保證其完整性或正確性；針對重要資訊，請使用者自行查證並以學校官方公告為準；課程相關資訊係搜集整理自學校官方公告、網站，與其他官方資料，採用合理使用原則提供同學參考使用；使用本網站即表示同意此免責聲明之內容。
        </p>
      </section>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="about-contact"
      >
        <h3 class="text-lg font-semibold text-theme-800 dark:text-zinc-100">
          聯絡與相關連結
        </h3>
        <ul class="mt-3 space-y-2 text-sm text-theme-700 dark:text-zinc-300">
          <li>
            聯絡作者：
            <a
              href="mailto:nou-tools-contact@binota.org"
              class="underline hover:text-theme-900 dark:hover:text-zinc-100"
              >nou-tools-contact@binota.org</a
            >
          </li>
          <li>
            <a
              href="https://kuma.binota.org/status/nou"
              class="underline hover:text-theme-900 dark:hover:text-zinc-100"
              target="_blank"
              rel="noopener noreferrer"
              >學校網站狀態</a
            >
          </li>
        </ul>
      </section>

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
