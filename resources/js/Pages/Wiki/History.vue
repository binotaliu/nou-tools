<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import { formatWikiDateTime } from '../../Composables/useWikiDates'

defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <Head :title="`編輯歷史：${viewModel.page.title} - NOU 小幫手`" />

  <AppLayout>
    <div class="mx-auto max-w-4xl space-y-6">
      <nav aria-label="麵包屑" class="text-sm">
        <Link
          :href="viewModel.page.url"
          class="inline-flex items-center gap-1 text-theme-700 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100"
        >
          <Icon name="chevron-left" class="size-3" />
          {{ viewModel.page.title }}
        </Link>
      </nav>

      <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
        編輯歷史
      </h2>

      <ol
        class="divide-y divide-theme-200 rounded-lg border border-theme-200 bg-white dark:divide-zinc-700 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="wiki-history"
      >
        <li
          v-for="revision in viewModel.revisions"
          :key="revision.id"
          class="flex flex-wrap items-baseline justify-between gap-2 px-5 py-4"
        >
          <div>
            <Link
              :href="revision.url"
              class="font-medium text-theme-900 underline hover:no-underline dark:text-zinc-100"
              >{{ revision.editSummary || '（沒有摘要）' }}</Link
            >
            <span
              v-if="revision.isCurrent"
              class="ml-2 rounded-full bg-theme-100 px-2 py-0.5 text-xs text-theme-800 dark:bg-zinc-800 dark:text-zinc-300"
              >目前版本</span
            >
            <p class="text-sm text-theme-700 dark:text-zinc-400">
              {{ revision.editorName }} · {{ revision.originLabel }}
            </p>
          </div>
          <time
            :datetime="revision.createdAt"
            class="text-sm text-theme-700 dark:text-zinc-400"
            >{{ formatWikiDateTime(revision.createdAt) }}</time
          >
        </li>
      </ol>
    </div>
  </AppLayout>
</template>
