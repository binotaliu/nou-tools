<script setup>
// Purely static content (no ViewModel/props): the licensing notices that
// used to live in the footer, plus the analytics-consent toggle (state comes
// from HandleInertiaRequests' shared `analyticsConsent` prop, same as the
// cookie-consent banner).
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import useAnalyticsConsent from '../../Composables/useAnalyticsConsent'

const { granted, toggle, error } = useAnalyticsConsent()

// The footer's links as bordered buttons: in an installed PWA the footer is hidden
// and this page is where they live (see BottomNav.vue, AdaptableNav.vue).
const linkButtonClass =
  'inline-flex items-center justify-center gap-2 rounded-lg border border-theme-300 bg-theme-50 px-3 py-2 text-sm font-medium text-theme-800 transition-colors hover:border-theme-400 hover:bg-theme-100 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:border-zinc-500 dark:hover:bg-zinc-700'
const linkIconClass = 'size-4 shrink-0 text-theme-700 dark:text-zinc-200'
</script>

<template>
  <Head title="關於 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-8">
      <!-- One header, two looks: on the web the icon sits beside the page
           title like a page heading; in a phone PWA (no header, no title) it
           becomes a centred app-style brand block. -->
      <div
        class="flex items-center gap-4 bottom-nav:flex-col bottom-nav:gap-1"
        data-testid="about-brand"
      >
        <div
          class="shrink-0 rounded-xl bg-theme-100 p-3 dark:bg-zinc-800 bottom-nav:bg-transparent dark:bottom-nav:bg-transparent"
        >
          <Icon
            name="book-open"
            class="size-8 text-theme-700 dark:text-zinc-300 bottom-nav:size-6"
          />
        </div>
        <div class="bottom-nav:text-center">
          <h2
            class="text-3xl font-bold tracking-tight text-theme-700 dark:text-zinc-200 bottom-nav:hidden"
            data-testid="about-title"
          >
            關於 NOU 小幫手
          </h2>
          <p
            class="mt-1 text-theme-700 dark:text-zinc-400 bottom-nav:hidden"
            data-testid="about-subtitle"
          >
            NOU 小幫手是給 NOU 同學的非官方小工具，由學生自發製作。
          </p>
          <p
            class="hidden text-lg font-semibold text-theme-700 dark:text-zinc-300 bottom-nav:block"
          >
            NOU 小幫手
          </p>
          <p
            class="mt-1 hidden text-xs text-theme-700 dark:text-zinc-400 bottom-nav:block"
          >
            給 NOU 同學的非官方小工具
          </p>
        </div>
      </div>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        id="about-disclaimer"
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
        <div class="mt-4 grid gap-2 sm:grid-cols-2">
          <a
            href="mailto:nou-tools-contact@binota.org"
            :class="linkButtonClass"
            data-testid="about-link-contact"
          >
            <Icon name="envelope" :class="linkIconClass" />
            聯絡作者
          </a>
          <Link
            href="/accessibility"
            :class="linkButtonClass"
            data-testid="about-link-accessibility"
          >
            <Icon name="eye" :class="linkIconClass" />
            無障礙說明
          </Link>
          <Link
            href="/share"
            :class="linkButtonClass"
            data-testid="about-link-share"
          >
            <Icon name="share" :class="linkIconClass" />
            分享 NOU 小幫手
          </Link>
          <Link
            href="/changelog"
            :class="linkButtonClass"
            data-testid="about-link-changelog"
          >
            <Icon name="sparkles" :class="linkIconClass" />
            更新日誌
          </Link>
          <Link
            href="/install"
            :class="[linkButtonClass, 'pwa:hidden']"
            data-testid="about-link-install"
          >
            <Icon name="device-phone-mobile" :class="linkIconClass" />
            安裝 NOU 小幫手
          </Link>
          <a
            href="https://kuma.binota.org/status/nou"
            :class="linkButtonClass"
            target="_blank"
            rel="noopener noreferrer"
            data-testid="about-link-status"
          >
            <Icon name="computer-desktop" :class="linkIconClass" />
            學校網站狀態
          </a>
          <a
            href="https://github.com/binotaliu/nou-tools"
            :class="linkButtonClass"
            target="_blank"
            rel="noopener noreferrer"
            data-testid="about-link-source"
          >
            <Icon name="code-bracket" :class="linkIconClass" />
            網站原始碼
          </a>
        </div>
      </section>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="about-analytics-consent"
      >
        <h3 class="text-lg font-semibold text-theme-800 dark:text-zinc-100">
          隱私與 Cookie
        </h3>
        <p
          class="mt-3 text-justify text-sm leading-relaxed text-theme-700 dark:text-zinc-300"
        >
          本站使用 Cookie 透過 Google Analytics
          蒐集匿名使用資料，以了解使用情形並改善服務。您可以隨時在這裡調整。
        </p>

        <div class="mt-4 flex items-center justify-between gap-4">
          <p
            id="about-analytics-consent-label"
            class="text-sm font-medium text-theme-800 dark:text-zinc-200"
          >
            分析 Cookie（Google Analytics）
          </p>
          <button
            type="button"
            role="switch"
            :aria-checked="granted"
            aria-labelledby="about-analytics-consent-label"
            :class="
              granted
                ? 'bg-theme-700 dark:bg-zinc-300'
                : 'bg-theme-200 dark:bg-zinc-700'
            "
            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors"
            data-testid="about-analytics-consent-toggle"
            @click="toggle"
          >
            <span
              :class="granted ? 'translate-x-6' : 'translate-x-1'"
              class="inline-block size-4 transform rounded-full bg-white shadow transition-transform dark:bg-zinc-900"
            ></span>
          </button>
        </div>

        <p
          v-if="error"
          class="mt-2 text-sm text-red-600 dark:text-red-400"
          data-testid="about-analytics-consent-error"
        >
          {{ error }}
        </p>
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
            class="inline-block py-1 text-theme-700 underline hover:text-theme-900 dark:text-zinc-300 dark:hover:text-zinc-100"
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
