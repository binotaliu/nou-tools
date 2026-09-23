<script setup>
// The rendered body is plain HTML from Markdown (RenderChangelogMarkdown)
// mounted with v-html, so it needs useMarkdownContainers to hydrate any
// :::tabs / :::checklist / :::countdown containers, same as
// Articles/Newsletter. It watches the post because Inertia reuses this
// component when paging between changelog posts.
import { computed, ref } from 'vue'
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

const post = computed(() => props.viewModel.post)

const pageTitle = computed(() => `${post.value.title} - NOU 小幫手`)

function formatDate(value) {
  if (!value) {
    return ''
  }

  return new Date(value).toLocaleDateString('zh-TW', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const contentRoot = ref(null)

useMarkdownContainers(contentRoot, [() => props.viewModel.post])
</script>

<template>
  <Head :title="pageTitle" />

  <AppLayout>
    <article
      class="mx-auto max-w-3xl rounded-lg border border-theme-200 bg-white p-6 shadow-sm sm:p-8 dark:border-zinc-700 dark:bg-zinc-900"
    >
      <header
        class="mb-6 space-y-2 border-b border-theme-200 pb-6 dark:border-zinc-700"
      >
        <p class="text-sm text-theme-700 dark:text-zinc-400">
          <Link href="/changelog" class="underline hover:no-underline">
            {{ viewModel.title }}
          </Link>
        </p>
        <h1 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          {{ post.title }}
        </h1>
        <p
          v-if="!post.isPublished"
          class="inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-1 text-sm font-medium text-amber-900 dark:bg-amber-900/40 dark:text-amber-100"
          data-testid="changelog-preview-badge"
        >
          <Icon name="eye" class="size-4" />
          預覽：{{ post.statusLabel }}，尚未公開
        </p>
        <p v-else class="text-sm text-theme-700 dark:text-zinc-400">
          {{ formatDate(post.publishedAt) }}
        </p>
      </header>

      <div
        ref="contentRoot"
        class="prose max-w-none prose-theme dark:prose-invert"
        v-html="post.bodyHtml"
      ></div>

      <nav
        v-if="viewModel.previousPost || viewModel.nextPost"
        class="mt-8 flex justify-between border-t border-theme-200 pt-4 text-sm dark:border-zinc-700"
      >
        <Link
          v-if="viewModel.previousPost"
          :href="viewModel.previousPost.url"
          class="text-theme-700 underline hover:no-underline dark:text-zinc-300"
        >
          較舊的更新
        </Link>
        <span v-else></span>
        <Link
          v-if="viewModel.nextPost"
          :href="viewModel.nextPost.url"
          class="text-theme-700 underline hover:no-underline dark:text-zinc-300"
        >
          較新的更新
        </Link>
      </nav>
    </article>
  </AppLayout>
</template>
