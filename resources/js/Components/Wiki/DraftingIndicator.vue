<script setup>
// Shown while the AI editor writes (or rewrites) a draft: a page whose lines
// write themselves out, the step the editor is probably on and how long it
// has taken. The steps are a guess paced by the clock (the editor reports no
// progress), so they are hidden from screen readers; the status sentence is
// what gets announced, once. Under reduced motion (the OS setting or the app's own toggle) the lines
// stand still and fill in step by step.
import { computed, onMounted, onUnmounted, ref } from 'vue'
import Icon from '../Icon.vue'

const props = defineProps({
  isRefinement: {
    type: Boolean,
    default: false,
  },
  hasSources: {
    type: Boolean,
    default: false,
  },
})

const STEP_SECONDS = 8

const steps = computed(() =>
  [
    props.isRefinement ? '正在確認調整說明' : '正在確認修改說明',
    props.hasSources ? '正在查證來源' : '正在檢視知識庫裡的相關條目',
    props.isRefinement ? '正在修改草稿' : '正在撰寫內容',
    '正在檢查格式與連結',
  ].map(step => `${step}…`)
)

const LINE_WIDTHS = ['w-2/5', 'w-full', 'w-11/12', 'w-4/5', 'w-full', 'w-3/5']

const startedAt = Date.now()
const elapsedSeconds = ref(0)
let timer = null

onMounted(() => {
  timer = window.setInterval(() => {
    elapsedSeconds.value = Math.floor((Date.now() - startedAt) / 1000)
  }, 1000)
})

onUnmounted(() => window.clearInterval(timer))

// Walks through the steps once and stays on the last one.
const currentStepIndex = computed(() =>
  Math.min(
    Math.floor(elapsedSeconds.value / STEP_SECONDS),
    steps.value.length - 1
  )
)

const currentStep = computed(() => steps.value[currentStepIndex.value])

// Under reduced motion nothing animates, so the page fills in one step at a
// time instead: the lines past this count stay dimmed.
const writtenLines = computed(() => currentStepIndex.value + 3)

const elapsedLabel = computed(() => {
  const minutes = Math.floor(elapsedSeconds.value / 60)
  const seconds = String(elapsedSeconds.value % 60).padStart(2, '0')

  return `${minutes}:${seconds}`
})
</script>

<template>
  <div
    class="flex flex-col gap-5 rounded-lg border border-theme-200 bg-white p-6 sm:flex-row sm:items-center dark:border-zinc-700 dark:bg-zinc-900"
    data-testid="wiki-request-drafting"
  >
    <div
      class="relative mx-auto w-40 shrink-0 rounded-md border border-theme-200 bg-theme-50 p-4 shadow-sm sm:mx-0 dark:border-zinc-700 dark:bg-zinc-800"
      aria-hidden="true"
    >
      <div class="space-y-2">
        <div
          v-for="(width, index) in LINE_WIDTHS"
          :key="index"
          class="h-2 animate-write-line rounded-full transition-opacity motion-reduce:animate-none"
          :class="[
            width,
            index >= writtenLines ? 'motion-reduce:opacity-30' : '',
            index === 0
              ? 'mb-3 h-3 bg-theme-500 dark:bg-zinc-400'
              : 'bg-theme-300 dark:bg-zinc-600',
          ]"
          :style="{ animationDelay: `${index * 0.35}s` }"
        ></div>
      </div>
      <Icon
        name="pencil-square"
        class="absolute -right-3 -bottom-3 size-8 rounded-full bg-white p-1.5 text-theme-700 shadow dark:bg-zinc-900 dark:text-zinc-200"
      />
    </div>

    <div class="min-w-0 space-y-2">
      <p
        role="status"
        class="flex items-center gap-2 font-semibold text-theme-900 dark:text-zinc-100"
      >
        <Icon
          name="sparkles"
          class="size-5 shrink-0 animate-pulse motion-reduce:animate-none"
        />
        {{ isRefinement ? 'AI 編輯正在依照你的調整修改…' : 'AI 編輯正在撰寫…' }}
      </p>
      <p
        class="text-sm text-theme-700 dark:text-zinc-400"
        aria-hidden="true"
        data-testid="wiki-drafting-step"
      >
        {{ currentStep }} <span class="tabular-nums">{{ elapsedLabel }}</span>
      </p>
      <p class="text-sm text-theme-800 dark:text-zinc-300">
        通常需要一到兩分鐘。完成後會自動送出，你可以放心關閉這個頁面；留在這裡的話，完成後還可以再調整。
      </p>
    </div>
  </div>
</template>
