<script setup>
// Purely static marketing content (no ViewModel/props), so this page only
// needs the layout chrome and its own inline markup.
import { Head } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import IconAppStoreDownload from '../../Components/IconAppStoreDownload.vue'
import IconGooglePlayDownload from '../../Components/IconGooglePlayDownload.vue'

const faqs = [
  {
    question: 'Alt UU 是空大官方的 App 嗎？',
    answer:
      '不是。Alt UU 是由學生開發的第三方 App，與國立空中大學官方沒有任何隸屬或合作關係。',
  },
  {
    question: '支援哪些裝置？',
    answer:
      'App Store 版本支援 iPhone、iPad，以及搭載 Apple Silicon 的 Mac；Google Play 版本支援 Android 手機與平板。',
  },
  {
    question: '學習時數會同步到學校的數位學習平台嗎？',
    answer:
      '會的。開啟教材後，畫面右上方會顯示本次學習計時器。觀看完畢後按一下返回按鈕，就會自動保存本次的學習時數。\n顯示在教材目錄中的數字，即為學校數位學習平台裡所記錄的學習時數。',
  },
  {
    question: '可以從上次看到的地方繼續嗎？',
    answer:
      '可以。Alt UU 會記住你上次觀看的位置，再次開啟教材時會直接從上次離開的地方繼續。',
  },
  {
    question: 'Alt UU 和 NOU 小幫手有什麼關係？',
    answer:
      'Alt UU 與 NOU 小幫手是兩個分開的產品。Alt UU 支援整合部分「NOU 小幫手」，開啟後可以在 App 內直接檢視學校行事曆、視訊面授資訊與考古題等，這些資料是 Alt UU 從 NOU 小幫手的公開 API 取得的。\n製作 Alt UU 的目的是為了讓同學在行動裝置上更方便地瀏覽數位學習平台；而 NOU 小幫手則是提供各種學校沒提供的實用工具，例如課表、學習進度管理、自習室等。',
  },
  {
    question: 'Alt UU 是如何運作的？我的資料安全嗎？',
    answer:
      '運作方式請參閱下方的《Alt UU 運作原理說明》，資料的處理方式則請見《隱私權政策》。Alt UU 的原始碼也完整公開，任何人都能自行檢視。你的帳號密碼只會傳送給學校，不會傳送給 Alt UU 的伺服器或任何第三方。',
  },
  {
    question: '可以檢視或參與 Alt UU 的開發嗎？',
    answer:
      '可以。Alt UU 是自由且開放的軟體，原始碼以 AGPL-3.0-or-later 授權條款公開於 GitHub，歡迎檢視、修改或貢獻程式碼。',
  },
]
</script>

<template>
  <Head title="Alt UU - NOU 小幫手" />

  <AppLayout>
    <div class="space-y-10">
      <div
        class="rounded-xl border border-theme-200 bg-white px-8 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="flex w-full justify-center" aria-hidden="true">
          <Icon
            name="academic-cap"
            class="size-16 text-theme-600 dark:text-zinc-400"
          />
        </div>
        <h2
          class="mt-2 text-4xl font-bold tracking-tight text-theme-600 dark:text-zinc-400"
        >
          Alt UU
        </h2>
        <p
          class="mx-auto mt-4 max-w-xl text-lg text-theme-600 dark:text-zinc-400"
        >
          專為 NOU 同學打造的 UU 平台瀏覽器 App。
          <br class="hidden sm:inline" />
          隨時隨地在行動裝置上輕鬆學習。
        </p>

        <div
          class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row"
        >
          <div class="flex w-47.25 justify-center sm:justify-end">
            <a
              href="https://apps.apple.com/tw/app/alt-uu/id6760690577"
              target="_blank"
              rel="noopener noreferrer"
            >
              <IconAppStoreDownload
                class="h-14"
                aria-label="在 App Store 上下載"
              />
            </a>
          </div>

          <a
            href="https://play.google.com/store/apps/details?id=org.binota.alt_uu"
            target="_blank"
            rel="noopener noreferrer"
          >
            <IconGooglePlayDownload
              class="h-14"
              aria-label="在 Google Play 上下載"
            />
          </a>
        </div>

        <p class="mt-3 text-xs text-theme-400 dark:text-zinc-500">
          App Store 支援 iPhone、iPad 及 Mac（Apple Silicon）
        </p>
      </div>

      <!-- Features -->
      <div>
        <h3
          class="sr-only mb-4 text-xl font-semibold text-theme-800 dark:text-zinc-200"
        >
          App 功能介紹
        </h3>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div
            class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <div
              class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-800 ring-1 ring-amber-300/70 dark:bg-amber-500/20 dark:text-amber-400 dark:ring-amber-400/40"
            >
              <Icon name="academic-cap" class="size-6" />
            </div>
            <h4
              class="mb-2 text-lg font-semibold text-theme-900 dark:text-zinc-100"
            >
              瀏覽 UU 平台教材
            </h4>
            <p class="text-sm text-theme-600 dark:text-zinc-400">
              以 App 瀏覽 UU
              平台，讓你在行動裝置上輕鬆存取所有課程教材，不需使用瀏覽器開啟電腦版網頁。
            </p>
          </div>

          <div
            class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <div
              class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-500/15 text-sky-800 ring-1 ring-sky-300/70 dark:bg-sky-500/20 dark:text-sky-400 dark:ring-sky-400/40"
            >
              <Icon name="clock" class="size-6" />
            </div>
            <h4
              class="mb-2 text-lg font-semibold text-theme-900 dark:text-zinc-100"
            >
              保存學習時數
            </h4>
            <p class="text-sm text-theme-600 dark:text-zinc-400">
              開啟教材後，畫面右上方會顯示本次學習計時器。觀看完畢後點擊返回按鈕，即可自動保存本次學習時數。
            </p>
          </div>

          <div
            class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <div
              class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-500/15 text-violet-800 ring-1 ring-violet-300/70 dark:bg-violet-500/20 dark:text-violet-400 dark:ring-violet-400/40"
            >
              <Icon name="play" class="size-6" />
            </div>
            <h4
              class="mb-2 text-lg font-semibold text-theme-900 dark:text-zinc-100"
            >
              繼續上次觀看進度
            </h4>
            <p class="text-sm text-theme-600 dark:text-zinc-400">
              Alt UU
              會記住你上次觀看的位置，讓你再次開啟教材時能直接從上次離開的地方繼續學習。
            </p>
          </div>

          <div
            class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <div
              class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500/15 text-emerald-800 ring-1 ring-emerald-300/70 dark:bg-emerald-500/20 dark:text-emerald-400 dark:ring-emerald-400/40"
            >
              <Icon name="puzzle-piece" class="size-6" />
            </div>
            <h4
              class="mb-2 text-lg font-semibold text-theme-900 dark:text-zinc-100"
            >
              NOU 小幫手整合
            </h4>
            <p class="text-sm text-theme-600 dark:text-zinc-400">
              支援整合「NOU 小幫手」，開啟後即可在 App
              內直接檢視學校行事曆、視訊面授資訊，以及考古題等。
            </p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div
          class="flex flex-col items-start gap-4 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div>
            <p class="text-xs font-semibold text-theme-400 dark:text-zinc-500">
              iPhone / iPad / Mac
            </p>
            <h4
              class="mt-1 text-xl font-semibold text-theme-900 dark:text-zinc-100"
            >
              App Store
            </h4>
            <p class="mt-2 text-sm text-theme-600 dark:text-zinc-400">
              支援 iPhone、iPad，以及搭載 Apple Silicon 的 Mac。
            </p>
          </div>
          <a
            href="https://apps.apple.com/tw/app/alt-uu/id6760690577"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-auto inline-flex items-center justify-center gap-2 rounded-lg border border-theme-600 bg-theme-600 px-4 py-2 font-semibold text-white transition hover:bg-theme-700"
          >
            前往 App Store
          </a>
        </div>

        <div
          class="flex flex-col items-start gap-4 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div>
            <p class="text-xs font-semibold text-theme-400 dark:text-zinc-500">
              Android
            </p>
            <h4
              class="mt-1 text-xl font-semibold text-theme-900 dark:text-zinc-100"
            >
              Google Play
            </h4>

            <p class="mt-2 text-sm text-theme-600 dark:text-zinc-400">
              支援 Android 手機、平板。
            </p>
          </div>
          <a
            href="https://play.google.com/store/apps/details?id=org.binota.alt_uu"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-600 bg-theme-600 px-4 py-2 font-semibold text-white transition hover:bg-theme-700"
          >
            前往 Google Play
          </a>
        </div>
      </div>

      <!-- FAQ -->
      <div data-testid="alt-uu-faq">
        <h3
          class="mb-4 text-xl font-semibold text-theme-800 dark:text-zinc-200"
        >
          常見問題
        </h3>

        <div class="space-y-2">
          <details
            v-for="(faq, index) in faqs"
            :key="index"
            class="group rounded-lg border border-theme-200 bg-white px-5 py-3 dark:border-zinc-700 dark:bg-zinc-900"
            :data-testid="`alt-uu-faq-${index}`"
          >
            <summary
              class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-theme-900 dark:text-zinc-100 [&::-webkit-details-marker]:hidden"
            >
              {{ faq.question }}
              <Icon
                name="chevron-down"
                class="size-5 shrink-0 text-theme-400 transition-transform group-open:rotate-180 dark:text-zinc-500"
                aria-hidden="true"
              />
            </summary>
            <p
              class="mt-2 text-sm whitespace-pre-line text-theme-600 dark:text-zinc-400"
            >
              {{ faq.answer }}
            </p>
          </details>
        </div>
      </div>

      <!-- Trademark Credit -->
      <div
        class="flex flex-col gap-2 text-xs text-theme-400 dark:text-zinc-500"
      >
        <p>
          使用 Alt UU，你必須同意並遵守 Alt UU 的《
          <a
            href="https://alt-uu-statics.wcsvdzeimhwq.workers.dev/usage-policy"
            target="_blank"
            rel="noopener noreferrer"
            class="text-theme-600 underline hover:text-theme-800 dark:text-zinc-400 dark:hover:text-zinc-200"
          >
            使用條款
          </a>
          》與 《
          <a
            href="https://alt-uu-statics.wcsvdzeimhwq.workers.dev/privacy-policy"
            target="_blank"
            rel="noopener noreferrer"
            class="text-theme-600 underline hover:text-theme-800 dark:text-zinc-400 dark:hover:text-zinc-200"
          >
            隱私權政策
          </a>
          》。
        </p>
        <p>
          Alt UU 並非國立空中大學官方產品，僅為學生開發的第三方
          App，與國立空中大學官方無任何隸屬或合作關係。有關 Alt UU
          之運作方式，請參閱《
          <a
            href="https://alt-uu-statics.wcsvdzeimhwq.workers.dev/app-architecture"
            target="_blank"
            rel="noopener noreferrer"
            class="text-theme-600 underline hover:text-theme-800 dark:text-zinc-400 dark:hover:text-zinc-200"
          >
            Alt UU 運作原理說明
          </a>
          》。
        </p>
        <p>
          Alt UU 是自由且開放之軟體，原始碼以 AGPL-3.0-or-later 授權條款公開於
          <a
            href="https://github.com/binotaliu/alt-uu"
            target="_blank"
            rel="noopener noreferrer"
            class="text-theme-600 underline hover:text-theme-800 dark:text-zinc-400 dark:hover:text-zinc-200"
          >
            GitHub
          </a>
          ，歡迎任何人檢視、審閱、使用、修改或貢獻程式碼。
        </p>
        <p>
          iPhone, iPad, Mac, and App Store are trademarks of Apple Inc.,
          registered in the U.S. and other countries.
          <br />
          Android is a trademark of Google LLC.
          <br />
          This site is not endorsed by or affiliated with Apple Inc. or Google
          LLC.
        </p>
      </div>
    </div>
  </AppLayout>
</template>
