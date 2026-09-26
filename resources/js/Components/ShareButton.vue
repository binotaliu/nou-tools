<script setup>
// The 分享 button and its copy-link fallback modal, shared by the article and
// newsletter pages. Behaviour lives in `useArticleShare`: the native share
// sheet when the browser has one, otherwise the modal below.
// `testIdPrefix` keeps each page's own test ids (`article-share-button`,
// `newsletter-share-button`, ...).
import { onMounted, onUnmounted, ref, watch } from 'vue'
import Icon from './Icon.vue'
import useArticleShare from '../Composables/useArticleShare'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  url: {
    type: String,
    required: true,
  },
  text: {
    type: String,
    default: '',
  },
  dialogTitle: {
    type: String,
    required: true,
  },
  inputLabel: {
    type: String,
    required: true,
  },
  testIdPrefix: {
    type: String,
    required: true,
  },
})

const shareInput = ref(null)

const { showShareModal, copied, shareUrl, shareTitle, shareText, share, copy } =
  useArticleShare(
    { shareTitle: props.title, shareUrl: props.url, shareText: props.text },
    shareInput
  )

// Inertia reuses the page component between articles/issues, so the props can
// change under a mounted button.
watch(
  () => props.title,
  value => {
    shareTitle.value = value
  }
)
watch(
  () => props.url,
  value => {
    shareUrl.value = value
  }
)

watch(
  () => props.text,
  value => {
    shareText.value = value
  }
)

function handleEscape(event) {
  if (event.key === 'Escape') {
    showShareModal.value = false
  }
}

onMounted(() => window.addEventListener('keydown', handleEscape))
onUnmounted(() => window.removeEventListener('keydown', handleEscape))
</script>

<template>
  <button
    type="button"
    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-theme-200 bg-white px-3 py-1 text-sm font-semibold text-theme-900 transition hover:bg-theme-50 disabled:border-theme-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800 dark:disabled:border-zinc-800"
    :data-testid="`${testIdPrefix}-button`"
    @click="share()"
  >
    <Icon name="share" class="size-4" />
    分享
  </button>

  <Teleport to="body">
    <div
      v-if="showShareModal"
      class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:items-center sm:p-0"
    >
      <div
        class="fixed inset-0 bg-black/40"
        aria-hidden="true"
        @click="showShareModal = false"
      ></div>

      <div
        role="dialog"
        aria-modal="true"
        :data-testid="`${testIdPrefix}-modal`"
        class="relative max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto rounded-lg bg-white p-6 shadow-lg sm:max-h-[calc(100vh-2rem)] dark:bg-zinc-900"
        @click.self="showShareModal = false"
      >
        <h3
          class="mb-2 text-lg font-semibold text-theme-900 dark:text-zinc-100"
        >
          {{ dialogTitle }}
        </h3>

        <p
          v-if="text"
          class="mb-3 text-sm leading-relaxed text-theme-700 dark:text-zinc-300"
          :data-testid="`${testIdPrefix}-text`"
        >
          {{ text }}
        </p>

        <div
          class="flex items-stretch gap-3 rounded border border-theme-300 bg-white text-sm text-theme-700 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-400"
        >
          <input
            ref="shareInput"
            class="flex-1 px-3 py-2 font-mono break-all text-theme-700 dark:text-zinc-400"
            :value="shareUrl"
            readonly
            :aria-label="inputLabel"
            @click="$event.target.select()"
          />

          <button
            type="button"
            class="my-1 mr-1 inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-theme-200 bg-theme-200 px-3 py-1 text-sm font-semibold whitespace-nowrap text-theme-900 transition hover:bg-theme-300 disabled:bg-theme-100 dark:border-zinc-700 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600 dark:disabled:bg-zinc-900"
            :aria-pressed="copied.toString()"
            :data-testid="`${testIdPrefix}-copy`"
            @click="copy()"
          >
            <span v-show="!copied">
              <Icon name="clipboard-document" class="inline size-4" />
              複製連結
            </span>
            <span v-show="copied">
              <Icon name="check" class="inline size-4" />
              已複製！
            </span>
          </button>
        </div>

        <div class="mt-4 flex justify-end">
          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 text-sm font-semibold text-theme-900 transition hover:bg-theme-50 disabled:border-theme-200 disabled:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800 dark:disabled:border-zinc-700 dark:disabled:bg-zinc-950"
            :data-testid="`${testIdPrefix}-close`"
            @click="showShareModal = false"
          >
            關閉
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
