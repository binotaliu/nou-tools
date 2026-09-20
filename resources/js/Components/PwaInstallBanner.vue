<script setup>
import { Link } from '@inertiajs/vue3'
import Icon from './Icon.vue'
import usePwaInstallBanner from '../Composables/usePwaInstallBanner'

const { visible, isIos, showDismissedNotice, install, close, optOut } =
  usePwaInstallBanner()
</script>

<template>
  <div v-show="visible" class="mb-6 print:hidden" data-testid="pwa-banner">
    <div
      class="relative rounded-lg border border-theme-300 dark:border-zinc-600"
      role="region"
    >
      <button
        type="button"
        class="absolute top-4 right-4 inline-flex items-center justify-center rounded-md border border-theme-600 bg-white p-1.5 text-theme-700 transition hover:bg-theme-100 hover:text-theme-900 focus:ring-2 focus:ring-theme-500 focus:outline-none dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-900 dark:hover:text-zinc-100"
        aria-label="關閉安裝提示"
        data-testid="pwa-banner-close"
        @click="close()"
      >
        <Icon name="x-mark" class="size-4" />
      </button>

      <div
        class="flex flex-col overflow-hidden rounded-lg bg-white sm:flex-row dark:bg-zinc-900"
      >
        <div
          class="flex h-24 min-h-24 items-center justify-center bg-theme-500/10 px-4 text-theme-700 sm:h-auto sm:w-24 sm:px-3 dark:bg-theme-500/15 dark:text-theme-400"
        >
          <Icon name="device-phone-mobile" class="size-6" />
        </div>

        <div
          class="flex flex-1 flex-col justify-between gap-4 px-4 py-4 text-theme-900 sm:pr-12 md:px-5 md:py-5 md:pr-12 dark:text-theme-100"
        >
          <p
            v-if="showDismissedNotice"
            class="text-sm leading-6 md:text-base"
            data-testid="pwa-banner-notice"
          >
            好的，之後不會再顯示這個提示。想安裝時，可以到頁面最下方的「安裝 NOU
            小幫手」檢視安裝說明。
          </p>
          <p v-else-if="!isIos" class="text-sm leading-6 md:text-base">
            將「NOU 小幫手」安裝到裝置上，即可像一般 App
            一樣從主畫面開啟，並支援離線檢視此課表。
          </p>
          <p v-else class="text-sm leading-6 md:text-base">
            將「NOU 小幫手」加入主畫面，即可像一般 App
            一樣開啟：點選瀏覽器下方的分享圖示，再選擇「加入主畫面」。不確定怎麼操作？請按「檢視安裝說明」。
          </p>

          <div class="flex flex-wrap items-center justify-end gap-2 sm:-mr-8">
            <button
              v-if="showDismissedNotice"
              type="button"
              class="inline-flex items-center justify-center rounded-md bg-theme-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-theme-600 focus:ring-2 focus:ring-theme-500 focus:outline-none"
              data-testid="pwa-banner-notice-ok"
              @click="close()"
            >
              好
            </button>
            <template v-else>
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-md border border-theme-600 bg-white px-4 py-2 text-sm font-medium text-theme-700 transition hover:bg-theme-100 focus:ring-2 focus:ring-theme-500 focus:outline-none dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                data-testid="pwa-banner-opt-out"
                @click="optOut()"
              >
                不再提示我安裝
              </button>
              <Link
                v-if="isIos"
                href="/install"
                class="inline-flex items-center justify-center rounded-md bg-theme-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-theme-600 focus:ring-2 focus:ring-theme-500 focus:outline-none"
                data-testid="pwa-banner-install-guide"
              >
                檢視安裝說明
              </Link>
              <button
                v-else
                type="button"
                class="inline-flex items-center justify-center rounded-md bg-theme-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-theme-600 focus:ring-2 focus:ring-theme-500 focus:outline-none"
                data-testid="pwa-banner-install"
                @click="install()"
              >
                安裝為 App
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
