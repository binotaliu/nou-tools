<script setup>
// Shown by /schedules/my when this browser has no remembered schedule. The
// visitor either starts a new schedule or brings an existing one over by
// pasting its link / scanning its QR code; the POST sets the same cookie the
// schedule page's "remember" modal does, then redirects to the schedule.
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import QrScanner from '../../Components/QrScanner.vue'
import decodeQrImage from '../../Composables/decodeQrImage'

const hasCreatedBefore = ref(false)
const scanning = ref(false)

const form = useForm({ url: '' })

const canScan = computed(
  () =>
    typeof navigator !== 'undefined' &&
    typeof navigator.mediaDevices?.getUserMedia === 'function'
)

function submit() {
  form.post('/schedules/my')
}

function onScanned(text) {
  scanning.value = false
  form.url = text
  submit()
}

// A screenshot of the backup card, picked from the photo library.
const imageError = ref('')

async function onImagePicked(event) {
  const [file] = event.target.files
  event.target.value = ''
  imageError.value = ''

  if (!file) {
    return
  }

  let text = null

  try {
    text = await decodeQrImage(file)
  } catch {
    // An unreadable file is reported the same way as one with no code in it.
  }

  if (!text) {
    imageError.value = '這張圖片裡找不到 QR Code，請確認是課表備份的截圖。'

    return
  }

  form.url = text
  submit()
}
</script>

<template>
  <Head title="找回我的課表 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-2xl">
      <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
        我的課表
      </h2>
      <p class="mt-2 text-sm text-theme-700 dark:text-zinc-400">
        這個<span class="pwa:hidden">瀏覽器</span
        ><span class="hidden pwa:inline">裝置</span
        >上還沒有記住任何課表。你之前建立過課表嗎？
      </p>

      <div class="mt-6 flex flex-col gap-3">
        <button
          type="button"
          data-testid="find-schedule-existing"
          data-analytics-event="find_schedule_existing"
          data-analytics-feature="schedule"
          :aria-pressed="hasCreatedBefore.toString()"
          class="rounded-lg border px-4 py-4 text-center font-semibold transition"
          :class="
            hasCreatedBefore
              ? 'border-theme-700 bg-theme-700 text-white'
              : 'border-theme-500 bg-white text-theme-900 hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800'
          "
          @click="hasCreatedBefore = true"
        >
          我先前建立過課表
        </button>
        <Link
          href="/schedules/create"
          data-testid="find-schedule-new"
          data-analytics-event="find_schedule_new"
          data-analytics-feature="schedule"
          class="rounded-lg border border-theme-500 bg-white px-4 py-4 text-center font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
        >
          我還沒有課表，建立新課表
        </Link>
      </div>

      <section
        v-if="hasCreatedBefore"
        data-testid="find-schedule-form"
        class="mt-6 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <h3
          class="mb-2 text-lg font-semibold text-theme-900 dark:text-zinc-100"
        >
          把課表記住在此<span class="pwa:hidden">瀏覽器</span
          ><span class="hidden pwa:inline">裝置</span>
        </h3>
        <p class="mb-4 text-sm text-theme-700 dark:text-zinc-400">
          在原裝置開啟課表，並在頁面最下方找到「備份課表連結」，複製並貼到下方；也可用相機掃描畫面上的
          QR Code，或選擇備份截圖來讀取。
        </p>
        <p
          data-testid="find-schedule-calendar-hint"
          class="mb-4 text-sm text-theme-700 dark:text-zinc-400"
        >
          如果你之前訂閱過行事曆，也可以在行事曆行程中的備註內找到課表連結。
        </p>

        <form @submit.prevent="submit">
          <label
            for="schedule-url"
            class="mb-1 block text-sm font-semibold text-theme-900 dark:text-zinc-100"
          >
            備份課表連結
          </label>
          <input
            id="schedule-url"
            v-model="form.url"
            type="text"
            inputmode="url"
            autocomplete="off"
            autocapitalize="off"
            spellcheck="false"
            placeholder="https://nou.tools/schedules/…"
            data-testid="find-schedule-url"
            :aria-invalid="form.errors.url ? 'true' : 'false'"
            :aria-describedby="form.errors.url ? 'schedule-url-error' : null"
            class="w-full rounded-lg border border-theme-300 bg-white px-3 py-2 text-theme-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
          />
          <p
            v-if="form.errors.url"
            id="schedule-url-error"
            role="alert"
            data-testid="find-schedule-error"
            class="mt-2 text-sm text-red-600 dark:text-red-400"
          >
            {{ form.errors.url }}
          </p>

          <p
            v-if="imageError"
            role="alert"
            data-testid="find-schedule-image-error"
            class="mt-2 text-sm text-red-600 dark:text-red-400"
          >
            {{ imageError }}
          </p>

          <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
            <label
              data-testid="find-schedule-image"
              class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition focus-within:outline-2 focus-within:outline-theme-500 hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
            >
              <Icon name="photo" class="size-4" />
              選取截圖
              <input
                type="file"
                accept="image/*"
                data-testid="find-schedule-image-input"
                class="sr-only"
                @change="onImagePicked"
              />
            </label>
            <button
              v-if="canScan"
              type="button"
              data-testid="find-schedule-scan"
              data-analytics-event="find_schedule_scan"
              data-analytics-feature="schedule"
              class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
              @click="scanning = true"
            >
              <Icon name="qr-code" class="size-4" />
              掃描 QR Code
            </button>
            <button
              type="submit"
              data-testid="find-schedule-submit"
              data-analytics-event="find_schedule_submit"
              data-analytics-feature="schedule"
              :disabled="form.processing || form.url.trim() === ''"
              class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-4 py-2 font-semibold text-white transition hover:bg-theme-800 disabled:opacity-50"
            >
              記住課表
            </button>
          </div>
        </form>
      </section>
    </div>

    <div
      v-if="scanning"
      data-testid="find-schedule-scanner-modal"
      class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:items-center sm:p-0"
    >
      <div class="fixed inset-0 bg-black/40" @click="scanning = false"></div>
      <div
        role="dialog"
        aria-modal="true"
        aria-label="掃描 QR Code"
        class="relative max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto rounded-lg bg-white p-6 shadow-lg sm:max-h-[calc(100vh-2rem)] dark:bg-zinc-900"
      >
        <h3
          class="mb-3 text-lg font-semibold text-theme-900 dark:text-zinc-100"
        >
          掃描課表 QR Code
        </h3>
        <QrScanner @detected="onScanned" @close="scanning = false" />
      </div>
    </div>
  </AppLayout>
</template>
