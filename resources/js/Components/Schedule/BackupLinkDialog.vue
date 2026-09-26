<script setup>
// The 備份課表連結 dialog. The schedule's URL is a credential (anyone holding
// it can edit the schedule, and it is the only way back to it once cookies
// are gone), so this is framed as saving a key, never as sharing.
//
// The card at the top is deliberately self-contained and always light: it is
// meant to be screenshotted (an installed PWA has no bookmarks), so the QR
// code, the schedule's name and what the picture is for all sit inside it.
// Find.vue can read such a screenshot back.
import { computed, ref } from 'vue'
import Icon from '../Icon.vue'
import useCopyLink from '../../Composables/useCopyLink'
import useDialogFocus from '../../Composables/useDialogFocus'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  backup: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close'])

const dialog = ref(null)
const copyInput = ref(null)
const { copied, copy } = useCopyLink({ shareUrl: props.backup.url }, copyInput)

const { onKeydown } = useDialogFocus(dialog, () => props.open)

const mailtoHref = computed(() => {
  const subject = encodeURIComponent(
    `我的 NOU 小幫手課表連結：${props.backup.name}`
  )
  const body = encodeURIComponent(
    `這是我的 NOU 小幫手課表連結，用來在清除瀏覽器資料或換裝置後找回課表。請勿轉傳給其他人。\n\n${props.backup.url}`
  )

  return `mailto:?subject=${subject}&body=${body}`
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:items-center sm:p-0"
      @keydown.esc.stop="emit('close')"
    >
      <div
        class="fixed inset-0 bg-black/40"
        aria-hidden="true"
        @click="emit('close')"
      ></div>

      <div
        ref="dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="schedule-backup-title"
        data-testid="schedule-backup-dialog"
        tabindex="-1"
        class="relative max-h-[calc(100dvh-2rem)] w-full max-w-sm overflow-y-auto rounded-lg bg-white p-4 shadow-lg sm:max-h-[calc(100vh-2rem)] dark:bg-zinc-900"
        @keydown="onKeydown"
      >
        <h3
          id="schedule-backup-title"
          class="mb-3 text-lg font-semibold text-theme-900 dark:text-zinc-100"
        >
          備份課表連結
        </h3>

        <!-- The screenshot target: always light, so the QR code scans in dark mode too. -->
        <div
          data-testid="schedule-backup-card"
          class="rounded-xl border border-zinc-200 bg-white p-5 text-center text-zinc-900"
        >
          <p
            class="flex items-center justify-center gap-1.5 text-sm font-semibold text-zinc-700"
          >
            <Icon name="lock-closed" class="size-4" />
            NOU 小幫手・課表備份
          </p>

          <div
            class="mx-auto mt-3 aspect-square w-full max-w-60 [&>svg]:size-full"
            data-testid="schedule-backup-qr"
            role="img"
            :aria-label="`${backup.name} 的課表連結 QR Code`"
            v-html="backup.qrCodeSvg"
          ></div>

          <p class="mt-3 text-base font-bold break-words">
            {{ backup.name }}
          </p>
          <p class="mt-1 text-xs leading-relaxed text-zinc-600">
            換裝置或清除瀏覽器資料後，在 NOU
            小幫手中依照指示掃描或選取這張圖片，即可找回課表。此 QR Code
            等同於課表的密碼，請勿與他人分享。
          </p>
        </div>

        <p class="mt-3 text-sm text-theme-700 dark:text-zinc-300">
          建議<strong>截圖存進相簿</strong>，或複製連結收進密碼管理員、寄給自己。
        </p>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
          <button
            type="button"
            data-testid="schedule-backup-copy"
            :aria-pressed="copied.toString()"
            class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold whitespace-nowrap text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
            @click="copy()"
          >
            <template v-if="copied">
              <Icon name="check" class="size-4" />
              已複製！
            </template>
            <template v-else>
              <Icon name="clipboard-document" class="size-4" />
              複製連結
            </template>
          </button>
          <a
            :href="mailtoHref"
            data-testid="schedule-backup-email"
            class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold whitespace-nowrap text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
          >
            <Icon name="envelope" class="size-4" />
            寄給自己
          </a>
        </div>

        <!-- Only here for the execCommand copy fallback to select. -->
        <input
          ref="copyInput"
          :value="backup.url"
          readonly
          tabindex="-1"
          aria-hidden="true"
          class="sr-only"
        />
        <div class="sr-only" role="status" aria-live="polite">
          {{ copied ? '已複製' : '' }}
        </div>

        <div class="mt-4 flex justify-end">
          <button
            type="button"
            data-testid="schedule-backup-close"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-4 py-2 font-semibold text-white transition hover:bg-theme-800"
            @click="emit('close')"
          >
            完成
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
