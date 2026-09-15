<script setup>
// Vue port of components/pwa-install-banner.blade.php +
// usePwaInstallBanner.js (the `nouPwaInstallBanner` Alpine.data()
// component). Not wired into any page yet (no pages are migrated); ported
// now so later phases can import it directly.
import Icon from './Icon.vue'
import usePwaInstallBanner from '../Composables/usePwaInstallBanner'

const { visible, isIos, install, dismiss } = usePwaInstallBanner()
</script>

<template>
  <div v-show="visible" class="mb-6 print:hidden">
    <div
      class="relative rounded-lg border border-warm-300 dark:border-zinc-600"
      role="region"
    >
      <button
        type="button"
        class="absolute top-4 right-4 inline-flex items-center justify-center rounded-md border border-warm-600 bg-white p-1.5 text-warm-700 transition hover:bg-warm-100 hover:text-warm-900 focus:ring-2 focus:ring-warm-500 focus:outline-none dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-900 dark:hover:text-zinc-100"
        aria-label="關閉安裝提示"
        @click="dismiss()"
      >
        <Icon name="x-mark" class="size-4" />
      </button>

      <div
        class="flex flex-col overflow-hidden rounded-lg bg-white sm:flex-row dark:bg-zinc-900"
      >
        <div
          class="flex h-24 min-h-24 items-center justify-center bg-warm-500/10 px-4 text-warm-700 sm:h-auto sm:w-24 sm:px-3 dark:bg-warm-500/15 dark:text-warm-400"
        >
          <Icon name="device-phone-mobile" class="size-6" />
        </div>

        <div
          class="flex flex-1 flex-col justify-between gap-4 px-4 py-4 text-warm-900 sm:pr-12 md:px-5 md:py-5 md:pr-12 dark:text-warm-100"
        >
          <p v-if="!isIos" class="text-sm leading-6 md:text-base">
            將「NOU 小幫手」安裝到裝置上，即可像一般 App
            一樣從主畫面開啟，並支援離線檢視此課表。
          </p>
          <p v-else class="text-sm leading-6 md:text-base">
            將「NOU 小幫手」加入主畫面，即可像一般 App
            一樣開啟：點選瀏覽器下方的分享圖示，再選擇「加入主畫面」。不確定怎麼操作？參考
            <a
              href="https://support.apple.com/zh-tw/guide/iphone/iph42ab2f3a7/ios"
              target="_blank"
              rel="noopener noreferrer"
              class="underline hover:text-warm-700 dark:hover:text-warm-300"
              >Apple 官方教學</a
            >中的「將網站圖像加入你的主畫面」章節（可在頁面中選擇你的 iOS
            版本）。
          </p>

          <div
            v-if="!isIos"
            class="flex flex-wrap items-center justify-end gap-2 sm:-mr-8"
          >
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-md bg-warm-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-warm-600 focus:ring-2 focus:ring-warm-500 focus:outline-none"
              @click="install()"
            >
              安裝為 App
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
