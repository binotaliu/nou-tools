<script setup>
import { Link } from '@inertiajs/vue3'
import useAnalyticsConsent from '../Composables/useAnalyticsConsent'

const { showBanner, accept, decline } = useAnalyticsConsent()
</script>

<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="translate-y-full opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-full opacity-0"
  >
    <div
      v-if="showBanner"
      data-testid="cookie-consent-banner"
      class="fixed inset-x-0 bottom-(--pwa-nav-height) z-50 border-t border-theme-200 bg-white pb-[env(safe-area-inset-bottom)] shadow-[0_-10px_40px_rgba(0,0,0,0.14)] dark:border-zinc-700 dark:bg-zinc-900 print:hidden"
    >
      <div
        class="mx-auto flex max-w-6xl flex-col items-center gap-4 px-6 py-4 sm:flex-row sm:justify-between"
      >
        <p class="text-sm text-theme-700 dark:text-zinc-300">
          本站使用 Cookie 透過 Google Analytics
          蒐集匿名使用資料，以了解使用情形並改善服務。您可以選擇是否同意，詳見
          <Link
            href="/about"
            class="underline hover:text-theme-900 dark:hover:text-zinc-100"
          >
            關於本站
          </Link>
          。
        </p>

        <div class="flex shrink-0 items-center gap-2">
          <button
            type="button"
            data-testid="cookie-consent-decline"
            class="inline-flex items-center justify-center rounded-lg border border-theme-500 bg-white px-4 py-2 text-sm font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
            @click="decline"
          >
            拒絕
          </button>
          <button
            type="button"
            data-testid="cookie-consent-accept"
            class="inline-flex items-center justify-center rounded-lg bg-theme-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-theme-800 dark:bg-zinc-300 dark:text-zinc-900 dark:hover:bg-zinc-200"
            @click="accept"
          >
            接受
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>
