<script setup>
// `viewModel.type` only survives Inertia's JSON serialization as the enum's
// string value (`kb` / `manual`), so the `App\Enums\ArticleType::label()`
// mapping is re-implemented here from that value. `viewModel.indexContent`
// is plain HTML (rendered Markdown) coming straight from the ViewModel, so
// it's rendered with v-html.
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import useMarkdownContainers from '../../Composables/useMarkdownContainers'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const ARTICLE_TYPE_LABELS = {
  kb: '知識庫',
  manual: '操作手冊',
}

const typeLabel = computed(
  () => ARTICLE_TYPE_LABELS[props.viewModel.type] ?? props.viewModel.type
)

const pageTitle = computed(() => `${typeLabel.value} - NOU 小幫手`)

const jsonLd = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'CollectionPage',
  name: pageTitle.value,
  url: typeof window !== 'undefined' ? window.location.href : undefined,
}))

// The index is itself rendered Markdown, so it can carry the same
// interactive containers an article can.
const indexContentRoot = ref(null)

useMarkdownContainers(indexContentRoot, [() => props.viewModel.indexContent])
</script>

<template>
  <Head :title="pageTitle">
    <script type="application/ld+json">
      {{ JSON.stringify(jsonLd) }}
    </script>
  </Head>

  <AppLayout>
    <div class="mx-auto max-w-4xl">
      <div
        class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div
          ref="indexContentRoot"
          class="prose max-w-none prose-theme dark:prose-invert"
          v-html="viewModel.indexContent"
        ></div>
      </div>
    </div>
  </AppLayout>
</template>
