<script setup>
// Single, status-driven error page. Laravel's exception handler
// (bootstrap/app.php) renders this via Inertia::render('Error', ['status'
// => $code]), following the standard Laravel+Inertia convention of one
// generic error component switching on status rather than a page per code.
//
// Intentionally NOT wrapped in AppLayout: it uses a stripped-down header
// (logo + home link only, no nav items) rather than the full site chrome,
// since a broken/expired page is exactly when the full nav (with its live
// theme/menu state) is least likely to be useful.
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Icon from '../Components/Icon.vue'

const props = defineProps({
  status: {
    type: Number,
    required: true,
  },
})

const MESSAGES = {
  401: 'Unauthorized',
  402: 'Payment Required',
  403: 'Forbidden',
  404: 'Not Found',
  419: 'Page Expired',
  429: 'Too Many Requests',
  500: 'Server Error',
  503: 'Service Unavailable',
}

const message = computed(() => MESSAGES[props.status] ?? 'Error')

// Mirrors the mailto link built server-side in the old errors::minimal
// layout, using client-observable equivalents of the same debugging info
// (path/UA/time) since this now renders after the response already left
// the server.
const mailtoLink = computed(() => {
  const baseEmail = 'nou-tools-error@binota.org'
  const subject = `NOU TOOLS 網頁錯誤 - ${new Date().toISOString()}`
  const info = {
    UA: typeof navigator !== 'undefined' ? navigator.userAgent : '',
    Time: new Date().toString(),
    Path: typeof window !== 'undefined' ? window.location.pathname : '',
    Status: props.status,
  }

  const body =
    '\n\n請將您的訊息寫在此行上方\n--------\n' +
    Object.entries(info)
      .map(([key, value]) => `${key}: ${value}`)
      .join('\n')

  return `mailto:${baseEmail}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
})

function goBack() {
  history.back()
}
</script>

<template>
  <Head :title="message" />

  <header
    class="sticky top-0 z-40 border-b border-warm-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
  >
    <div class="mx-auto max-w-7xl px-3 py-2 md:px-6 md:py-4">
      <h1
        class="inline-flex items-center gap-2 text-lg font-bold text-warm-700 md:gap-4 md:text-2xl dark:text-zinc-300"
      >
        <Icon
          name="book-open"
          class="size-5 shrink-0 text-warm-700 md:size-6 dark:text-zinc-300"
        />
        <Link href="/" class="shrink-0">NOU 小幫手</Link>
      </h1>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-6 py-8">
    <div class="flex min-h-[60vh] items-center justify-center">
      <div class="w-full max-w-md">
        <div
          class="rounded-lg border border-warm-200 bg-white p-8 dark:border-zinc-700 dark:bg-zinc-900"
          data-testid="error-page"
        >
          <div class="mb-6 text-center">
            <h2
              class="mb-2 text-4xl font-bold text-warm-600 dark:text-zinc-400"
              data-testid="error-status"
            >
              {{ status }}
            </h2>
            <p
              class="mb-4 text-2xl font-semibold text-warm-900 dark:text-zinc-100"
              data-testid="error-message"
            >
              {{ message }}
            </p>
            <p class="text-warm-900 dark:text-zinc-100">
              抱歉，發生了一些問題。如果問題持續，請
              <a
                class="text-warm-600 underline hover:no-underline dark:text-zinc-400"
                :href="mailtoLink"
              >
                點擊此連結寫信聯絡網站作者
              </a>
              。
            </p>
          </div>

          <div class="flex gap-3">
            <button
              type="button"
              data-testid="error-back-button"
              class="flex-1 rounded-md border border-warm-200 px-4 py-2 text-center font-medium text-warm-700 transition-colors hover:bg-warm-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
              @click="goBack"
            >
              回到上一頁
            </button>
            <Link
              href="/"
              class="flex-1 rounded-md bg-warm-600 px-4 py-2 text-center font-medium text-white transition-colors hover:bg-warm-700"
            >
              回到首頁
            </Link>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>
