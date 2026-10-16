<script setup>
// Status page of an edit request. While the AI editor is drafting (it runs
// after the response that created the request) the page polls itself; the
// draft is submitted as soon as it is written, so closing the page loses
// nothing. Afterwards the visitor sees what was published or queued for
// review, with its diff and preview, and can still refine it (再調整) or
// withdraw a queued one. Anyone with the link can watch; only the device's
// schedule that made the request gets the controls (`canRefine` etc.).
import { computed, onUnmounted, ref, watch } from 'vue'
import { Head, Link, router, useForm, usePage, usePoll } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Button from '../../../Components/Button.vue'
import FieldError from '../../../Components/FieldError.vue'
import Icon from '../../../Components/Icon.vue'
import DiffView from '../../../Components/Wiki/DiffView.vue'
import DraftingIndicator from '../../../Components/Wiki/DraftingIndicator.vue'
import useMarkdownContainers from '../../../Composables/useMarkdownContainers'
import { WIKI_PRIMARY_LINK } from '../../../Components/Wiki/linkClasses'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const request = computed(() => props.viewModel.request)
const status = computed(() => request.value.status)
const errors = computed(() => usePage().props.errors ?? {})

const { start, stop } = usePoll(
  3000,
  { only: ['viewModel'] },
  { autoStart: false }
)

watch(
  status,
  value => {
    if (value === 'drafting') {
      start()
    } else {
      stop()
    }
  },
  { immediate: true }
)

onUnmounted(stop)

const tab = ref('diff')

const previewRoot = ref(null)

useMarkdownContainers(previewRoot, [
  () => props.viewModel.request.draftContent,
  () => tab.value,
])

const heading = computed(() => {
  if (request.value.isNewPage) {
    return request.value.draftTitle
      ? `新條目：${request.value.draftTitle}`
      : '新條目'
  }

  return `修改：${request.value.page?.title ?? request.value.draftTitle ?? ''}`
})

const refineForm = useForm({ instruction: '' })
const processing = ref(false)

function post(path) {
  router.post(
    `/wiki/edit-requests/${request.value.uuid}/${path}`,
    {},
    {
      preserveScroll: true,
      onStart: () => (processing.value = true),
      onFinish: () => (processing.value = false),
    }
  )
}

function refine() {
  refineForm.post(`/wiki/edit-requests/${request.value.uuid}/refine`, {
    preserveScroll: true,
    onSuccess: () => refineForm.reset(),
  })
}

function abandon() {
  router.delete(`/wiki/edit-requests/${request.value.uuid}`)
}

// Drafts are submitted on their own; 確認送出 is only for requests left
// waiting from before that, or whose automatic submission didn't go through.
const hasActions = computed(
  () =>
    status.value !== 'drafting' &&
    request.value.isOwner &&
    (status.value === 'awaiting_confirmation' ||
      request.value.canRefine ||
      request.value.canAbandon)
)

const retryUrl = computed(() =>
  request.value.page ? `${request.value.page.url}/edit` : '/wiki/new'
)

const panelClasses =
  'rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900'
</script>

<template>
  <Head :title="`${heading} - 知識庫 - NOU 小幫手`" />

  <AppLayout>
    <div class="mx-auto max-w-4xl space-y-6">
      <nav aria-label="麵包屑" class="text-sm">
        <Link
          :href="request.page ? request.page.url : '/wiki'"
          class="inline-flex items-center gap-1 text-theme-700 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100"
        >
          <Icon name="chevron-left" class="size-3" />
          {{ request.page ? request.page.title : '知識庫' }}
        </Link>
      </nav>

      <header class="space-y-2">
        <p
          class="inline-block rounded-full bg-theme-100 px-3 py-1 text-xs font-semibold text-theme-800 dark:bg-zinc-800 dark:text-zinc-300"
          data-testid="wiki-request-status"
        >
          {{ request.statusLabel }}
        </p>
        <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          {{ heading }}
        </h2>
      </header>

      <section :class="panelClasses" aria-labelledby="wiki-request-instruction">
        <h3
          id="wiki-request-instruction"
          class="mb-2 font-semibold text-theme-900 dark:text-zinc-100"
        >
          修改說明
        </h3>
        <p class="whitespace-pre-line text-theme-800 dark:text-zinc-300">
          {{ request.instruction }}
        </p>
        <ol
          v-if="request.refinements.length"
          class="mt-3 list-decimal space-y-1 pl-6 text-sm text-theme-800 dark:text-zinc-300"
        >
          <li v-for="(refinement, index) in request.refinements" :key="index">
            再調整：{{ refinement }}
          </li>
        </ol>
        <ul v-if="request.sourceUrls.length" class="mt-3 text-sm">
          <li v-for="url in request.sourceUrls" :key="url">
            來源：<a
              :href="url"
              target="_blank"
              rel="noopener noreferrer"
              class="break-all underline hover:no-underline"
              >{{ url }}</a
            >
          </li>
        </ul>
      </section>

      <p
        v-if="errors.request"
        role="alert"
        class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-900 dark:border-red-800 dark:bg-red-950/50 dark:text-red-100"
      >
        {{ errors.request }}
      </p>

      <DraftingIndicator
        v-if="status === 'drafting'"
        :is-refinement="request.refinements.length > 0"
        :has-sources="request.sourceUrls.length > 0"
      />

      <div
        v-else-if="status === 'declined_by_ai'"
        role="status"
        :class="panelClasses"
        class="space-y-3"
      >
        <p class="font-semibold text-theme-900 dark:text-zinc-100">
          AI 編輯沒有採納這個修改。
        </p>
        <p
          class="text-theme-800 dark:text-zinc-300"
          data-testid="wiki-decline-reason"
        >
          {{ request.declineReason }}
        </p>
        <Link v-if="request.isOwner" :href="retryUrl" :class="WIKI_PRIMARY_LINK"
          >重新提出修改</Link
        >
      </div>

      <div
        v-else-if="status === 'failed'"
        role="status"
        :class="panelClasses"
        class="space-y-3"
      >
        <p class="text-theme-900 dark:text-zinc-100">
          這次沒有成功產生草稿，請換個說法或稍後再試。
        </p>
        <Link v-if="request.isOwner" :href="retryUrl" :class="WIKI_PRIMARY_LINK"
          >重新提出修改</Link
        >
      </div>

      <div
        v-else-if="status === 'pending_review'"
        role="status"
        :class="panelClasses"
        data-testid="wiki-request-pending"
      >
        <p class="text-theme-900 dark:text-zinc-100">
          已送出，正在等待管理員審核，通過後就會發布。
        </p>
        <p
          v-if="request.reviewReasons.length"
          class="mt-2 text-sm text-theme-700 dark:text-zinc-400"
        >
          需要審核的原因：{{ request.reviewReasons.join('、') }}
        </p>
        <p v-if="request.publishedUrl" class="mt-2 text-sm">
          <Link
            :href="request.publishedUrl"
            class="text-theme-900 underline hover:no-underline dark:text-zinc-100"
            >先前的版本</Link
          >已經發布，這次的調整通過審核後會接著發布。
        </p>
      </div>

      <div
        v-else-if="status === 'published'"
        role="status"
        :class="panelClasses"
        class="space-y-3"
        data-testid="wiki-request-published"
      >
        <p class="text-theme-900 dark:text-zinc-100">已發布，感謝你的貢獻！</p>
        <Link
          v-if="request.publishedUrl"
          :href="request.publishedUrl"
          :class="WIKI_PRIMARY_LINK"
          >前往條目</Link
        >
      </div>

      <div
        v-else-if="status === 'rejected'"
        role="status"
        :class="panelClasses"
      >
        <p class="text-theme-900 dark:text-zinc-100">
          管理員沒有採納這個修改。
        </p>
        <p
          v-if="request.reviewNote"
          class="mt-2 text-theme-800 dark:text-zinc-300"
        >
          {{ request.reviewNote }}
        </p>
      </div>

      <div
        v-else-if="status === 'conflict'"
        role="status"
        :class="panelClasses"
        class="space-y-3"
        data-testid="wiki-request-conflict"
      >
        <p class="text-theme-900 dark:text-zinc-100">
          在你確認之前，這個條目已經被其他人修改過，這份草稿不能直接套用。
        </p>
        <div v-if="request.isOwner" class="flex flex-wrap gap-2">
          <Button
            variant="primary"
            :disabled="processing"
            data-testid="wiki-request-redraft"
            @click="post('redraft')"
          >
            <Icon name="arrow-path" class="size-4" />
            依最新版本重新產生
          </Button>
          <Button variant="ghost" @click="abandon">放棄</Button>
        </div>
      </div>

      <div
        v-else-if="status === 'abandoned'"
        role="status"
        :class="panelClasses"
      >
        這個修改已經放棄。
      </div>

      <template v-if="request.draftContent">
        <section
          :class="panelClasses"
          class="space-y-4"
          aria-labelledby="wiki-draft"
        >
          <div class="space-y-1">
            <h3
              id="wiki-draft"
              class="text-xl font-bold text-theme-900 dark:text-zinc-100"
            >
              {{ request.draftTitle }}
            </h3>
            <p
              class="text-sm text-theme-700 dark:text-zinc-400"
              data-testid="wiki-draft-summary"
            >
              {{ request.draftEditSummary }}
              <template v-if="request.draftCategories.length">
                · 分類：{{ request.draftCategories.join('、') }}</template
              >
              <template v-if="request.draftSlug">
                · 網址：/wiki/{{ request.draftSlug }}</template
              >
            </p>
          </div>

          <p
            v-if="request.lastRefinementFailed"
            role="alert"
            class="rounded-md bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950/50 dark:text-amber-100"
          >
            上一次的再調整沒有成功，以下仍是先前的草稿。<template
              v-if="request.lastRefinementDeclineReason"
              >編輯的說明：{{ request.lastRefinementDeclineReason }}</template
            >
          </p>

          <div role="tablist" aria-label="草稿檢視方式" class="flex gap-2">
            <Button
              role="tab"
              size="sm"
              :aria-selected="tab === 'diff'"
              :pressed="tab === 'diff'"
              data-testid="wiki-tab-diff"
              @click="tab = 'diff'"
            >
              差異
            </Button>
            <Button
              role="tab"
              size="sm"
              :aria-selected="tab === 'preview'"
              :pressed="tab === 'preview'"
              data-testid="wiki-tab-preview"
              @click="tab = 'preview'"
            >
              預覽
            </Button>
          </div>

          <DiffView v-if="tab === 'diff'" :lines="request.diff" />
          <div
            v-else
            ref="previewRoot"
            class="prose max-w-none prose-theme dark:prose-invert"
            data-testid="wiki-draft-preview"
            v-html="request.draftContent"
          ></div>
        </section>

        <section
          v-if="hasActions"
          :class="panelClasses"
          class="space-y-6"
          :aria-label="
            status === 'awaiting_confirmation' ? '確認草稿' : '調整修改'
          "
          data-testid="wiki-request-actions"
        >
          <div
            v-if="status === 'awaiting_confirmation' || request.canAbandon"
            class="flex flex-wrap gap-2"
          >
            <Button
              v-if="status === 'awaiting_confirmation'"
              variant="primary"
              :disabled="processing"
              data-testid="wiki-request-submit"
              @click="post('submit')"
            >
              <Icon name="check" class="size-4" />
              確認送出
            </Button>
            <Button
              v-if="request.canAbandon"
              variant="ghost"
              data-testid="wiki-request-abandon"
              @click="abandon"
            >
              {{ status === 'pending_review' ? '撤回' : '放棄' }}
            </Button>
          </div>

          <form
            v-if="request.canRefine"
            class="space-y-2"
            @submit.prevent="refine"
          >
            <label
              for="refine-instruction"
              class="block text-sm font-medium text-theme-700 dark:text-zinc-300"
              >{{
                status === 'published'
                  ? '發現問題？還可以再調整'
                  : '還想調整什麼？'
              }}（還可以再調整 {{ request.refinementsLeft }} 次）</label
            >
            <textarea
              id="refine-instruction"
              v-model="refineForm.instruction"
              rows="3"
              required
              :maxlength="viewModel.instructionMaxLength"
              placeholder="例如：第二段寫得太長了，請精簡成三句。"
              :aria-invalid="refineForm.errors.instruction ? 'true' : null"
              :aria-describedby="
                refineForm.errors.instruction
                  ? 'refine-instruction-error'
                  : null
              "
              class="w-full rounded-md border border-theme-300 bg-white px-3 py-2 text-theme-900 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
              data-testid="wiki-refine-instruction"
            ></textarea>
            <FieldError
              id="refine-instruction-error"
              :message="refineForm.errors.instruction"
            />
            <Button
              type="submit"
              :disabled="refineForm.processing"
              data-testid="wiki-request-refine"
            >
              <Icon name="arrow-path" class="size-4" />
              再調整
            </Button>
          </form>
        </section>
      </template>
    </div>
  </AppLayout>
</template>
