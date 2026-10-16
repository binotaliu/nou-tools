<script setup>
// The guides are repository files rendered through the wiki pipeline, and
// the same text the AI editor follows.
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import useMarkdownContainers from '../../Composables/useMarkdownContainers'

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
  <Head :title="`${viewModel.title} - 知識庫 - NOU 小幫手`" />

  <AppLayout>
    <div class="mx-auto max-w-4xl space-y-6">
      <nav aria-label="麵包屑" class="flex flex-wrap gap-4 text-sm">
        <Link
          href="/wiki"
          class="inline-flex items-center gap-1 text-theme-700 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100"
        >
          <Icon name="chevron-left" class="size-3" />
          知識庫
        </Link>
        <Link
          :href="viewModel.otherGuideUrl"
          class="text-theme-700 underline hover:no-underline dark:text-zinc-400"
          >{{ viewModel.otherGuideTitle }}</Link
        >
      </nav>

      <article
        class="rounded-lg border border-theme-200 bg-white p-6 shadow-sm sm:p-8 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <h1 class="mb-6 text-3xl font-bold text-theme-900 dark:text-zinc-100">
          {{ viewModel.title }}
        </h1>
        <div
          ref="contentRoot"
          class="prose max-w-none prose-theme dark:prose-invert"
          v-html="viewModel.content"
        ></div>
      </article>
    </div>
  </AppLayout>
</template>
