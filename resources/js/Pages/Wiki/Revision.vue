<script setup>
// One revision: what changed since the previous one (server-side diff), the
// request and sources behind an AI edit, and the revision as it read then.
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import DiffView from '../../Components/Wiki/DiffView.vue'
import useMarkdownContainers from '../../Composables/useMarkdownContainers'
import { formatWikiDateTime } from '../../Composables/useWikiDates'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const contentRoot = ref(null)

useMarkdownContainers(contentRoot, [() => props.viewModel.content])
</script>

<template>
  <Head
    :title="`${viewModel.revision.title}（${formatWikiDateTime(viewModel.revision.createdAt)} 版本） - NOU 小幫手`"
  />

  <AppLayout>
    <div class="mx-auto max-w-4xl space-y-6">
      <nav aria-label="麵包屑" class="flex flex-wrap gap-4 text-sm">
        <Link
          :href="viewModel.page.url"
          class="inline-flex items-center gap-1 text-theme-700 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100"
        >
          <Icon name="chevron-left" class="size-3" />
          {{ viewModel.page.title }}
        </Link>
        <Link
          :href="`${viewModel.page.url}/history`"
          class="text-theme-700 underline hover:no-underline dark:text-zinc-400"
          >編輯歷史</Link
        >
      </nav>

      <header class="space-y-2">
        <h2 class="text-2xl font-bold text-theme-900 dark:text-zinc-100">
          {{ viewModel.revision.editSummary || '（沒有摘要）' }}
        </h2>
        <p class="text-sm text-theme-700 dark:text-zinc-400">
          {{ viewModel.revision.originLabel }} ·
          {{ formatWikiDateTime(viewModel.revision.createdAt) }}
          <span v-if="viewModel.revision.isCurrent"> · 目前版本</span>
        </p>
      </header>

      <section
        v-if="viewModel.instruction"
        aria-labelledby="wiki-revision-request"
        class="space-y-2 rounded-lg border border-theme-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <h3
          id="wiki-revision-request"
          class="font-semibold text-theme-900 dark:text-zinc-100"
        >
          修改說明
        </h3>
        <p
          class="whitespace-pre-line text-theme-800 dark:text-zinc-300"
          data-testid="wiki-revision-instruction"
        >
          {{ viewModel.instruction }}
        </p>
        <ul v-if="viewModel.sourceUrls.length" class="text-sm">
          <li v-for="url in viewModel.sourceUrls" :key="url">
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

      <section aria-labelledby="wiki-revision-diff" class="space-y-2">
        <h3
          id="wiki-revision-diff"
          class="font-semibold text-theme-900 dark:text-zinc-100"
        >
          {{ viewModel.isFirstRevision ? '初始內容' : '與前一版的差異' }}
        </h3>
        <DiffView :lines="viewModel.diff" />
      </section>

      <section
        aria-labelledby="wiki-revision-content"
        class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <h3
          id="wiki-revision-content"
          class="mb-4 text-xl font-bold text-theme-900 dark:text-zinc-100"
        >
          {{ viewModel.revision.title }}
        </h3>
        <div
          ref="contentRoot"
          class="prose max-w-none prose-theme dark:prose-invert"
          v-html="viewModel.content"
        ></div>
      </section>
    </div>
  </AppLayout>
</template>
