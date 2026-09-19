<script setup>
// Intro, item summaries and column bodies are plain HTML rendered from
// Markdown server-side (RenderNewsletterMarkdown) and mounted with v-html.
// They all sit under one root so a single useMarkdownContainers call
// hydrates any :::tabs / :::checklist / :::countdown in them; it watches the
// issue because Inertia reuses this component when paging between issues.
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import useMarkdownContainers from '../../Composables/useMarkdownContainers'
import {
  formatNewsletterDate,
  formatNewsletterDateRange,
} from '../../Composables/useNewsletterDates'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const issue = computed(() => props.viewModel.issue)

const pageTitle = computed(() => `${issue.value.title} - NOU 小幫手`)

const centerGroups = computed(() => {
  const groups = new Map()

  issue.value.centerItems.forEach(item => {
    const group = groups.get(item.sourceName) ?? {
      sourceName: item.sourceName,
      items: [],
    }
    group.items.push(item)
    groups.set(item.sourceName, group)
  })

  return [...groups.values()]
})

const hasHighlights = computed(
  () =>
    issue.value.highlightsIntro !== '' || issue.value.highlightEvents.length > 0
)

const jsonLd = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'NewsArticle',
  headline: issue.value.title,
  datePublished: issue.value.publishedAt ?? issue.value.publishesOn,
  isPartOf: {
    '@type': 'Periodical',
    name: props.viewModel.newsletterTitle,
  },
}))

const contentRoot = ref(null)

useMarkdownContainers(contentRoot, [() => props.viewModel.issue])
</script>

<template>
  <Head :title="pageTitle">
    <link
      rel="alternate"
      type="application/atom+xml"
      :title="viewModel.newsletterTitle"
      :href="viewModel.feedUrl"
    />
    <script type="application/ld+json">
      {{ JSON.stringify(jsonLd) }}
    </script>
  </Head>

  <AppLayout>
    <article
      class="mx-auto max-w-3xl rounded-lg border border-theme-200 bg-white p-6 shadow-sm sm:p-8 dark:border-zinc-700 dark:bg-zinc-900"
    >
      <header
        class="mb-6 space-y-2 border-b border-theme-200 pb-6 dark:border-zinc-700"
      >
        <p class="text-sm text-theme-600 dark:text-zinc-400">
          <Link href="/newsletter" class="underline hover:no-underline">
            {{ viewModel.newsletterTitle }}
          </Link>
          ・{{ issue.issueKey }}
        </p>
        <h1 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          {{ issue.title }}
        </h1>
        <p class="text-sm text-theme-600 dark:text-zinc-400">
          {{ formatNewsletterDate(issue.publishesOn) }} 發刊
        </p>
        <p
          v-if="!issue.isPublished"
          class="inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-1 text-sm font-medium text-amber-900 dark:bg-amber-900/40 dark:text-amber-100"
          data-testid="newsletter-preview-badge"
        >
          <Icon name="eye" class="size-4" />
          預覽：{{ issue.statusLabel }}，尚未公開
        </p>
      </header>

      <div ref="contentRoot" class="space-y-10">
        <section
          v-if="hasHighlights"
          aria-labelledby="newsletter-highlights"
          data-testid="newsletter-highlights"
        >
          <h2
            id="newsletter-highlights"
            class="mb-3 text-2xl font-bold text-theme-900 dark:text-zinc-100"
          >
            本期重點事項
          </h2>
          <div
            v-if="issue.highlightsIntro"
            class="prose max-w-none prose-theme dark:prose-invert"
            v-html="issue.highlightsIntro"
          ></div>
          <ul
            v-if="issue.highlightEvents.length > 0"
            class="mt-4 space-y-2 rounded-lg bg-theme-50 p-4 dark:bg-zinc-800"
          >
            <li
              v-for="event in issue.highlightEvents"
              :key="`${event.startDate}-${event.name}`"
              class="flex flex-col gap-x-3 sm:flex-row"
            >
              <span
                class="shrink-0 font-mono text-sm text-theme-700 sm:w-44 dark:text-zinc-300"
              >
                {{ formatNewsletterDateRange(event.startDate, event.endDate) }}
              </span>
              <span class="text-theme-900 dark:text-zinc-100">
                {{ event.name }}
              </span>
            </li>
          </ul>
        </section>

        <section
          v-if="issue.newsItems.length > 0"
          aria-labelledby="newsletter-news"
          data-testid="newsletter-news"
        >
          <h2
            id="newsletter-news"
            class="mb-4 text-2xl font-bold text-theme-900 dark:text-zinc-100"
          >
            空大新消息
          </h2>
          <ul class="space-y-5">
            <li v-for="item in issue.newsItems" :key="item.id">
              <h3
                class="text-lg font-semibold text-theme-900 dark:text-zinc-100"
              >
                <a
                  v-if="item.url"
                  :href="item.url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="hover:underline"
                >
                  {{ item.headline }}
                  <Icon
                    name="arrow-top-right-on-square"
                    class="inline size-4 align-baseline text-theme-500"
                  />
                </a>
                <template v-else>{{ item.headline }}</template>
              </h3>
              <p class="text-sm text-theme-600 dark:text-zinc-400">
                {{ item.sourceName }}
              </p>
              <div
                v-if="item.summary"
                class="prose mt-1 max-w-none prose-theme dark:prose-invert"
                v-html="item.summary"
              ></div>
            </li>
          </ul>
        </section>

        <section
          v-if="centerGroups.length > 0"
          aria-labelledby="newsletter-centers"
          data-testid="newsletter-centers"
        >
          <h2
            id="newsletter-centers"
            class="mb-4 text-2xl font-bold text-theme-900 dark:text-zinc-100"
          >
            各中心消息
          </h2>
          <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
            <div v-for="group in centerGroups" :key="group.sourceName">
              <h3
                class="mb-2 border-l-4 border-theme-400 pl-2 font-semibold text-theme-800 dark:border-zinc-500 dark:text-zinc-200"
              >
                {{ group.sourceName }}
              </h3>
              <ul class="space-y-3">
                <li v-for="item in group.items" :key="item.id">
                  <a
                    v-if="item.url"
                    :href="item.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-medium text-theme-900 hover:underline dark:text-zinc-100"
                  >
                    {{ item.headline }}
                    <Icon
                      name="arrow-top-right-on-square"
                      class="inline size-4 align-baseline text-theme-500"
                    />
                  </a>
                  <span
                    v-else
                    class="font-medium text-theme-900 dark:text-zinc-100"
                    >{{ item.headline }}</span
                  >
                  <div
                    v-if="item.summary"
                    class="prose prose-sm max-w-none prose-theme dark:prose-invert"
                    v-html="item.summary"
                  ></div>
                </li>
              </ul>
            </div>
          </div>
        </section>

        <section
          v-for="column in issue.columns"
          :key="column.id"
          :aria-label="column.title"
          data-testid="newsletter-column"
        >
          <h2 class="text-2xl font-bold text-theme-900 dark:text-zinc-100">
            {{ column.title }}
          </h2>
          <p
            v-if="column.author"
            class="mb-3 text-sm text-theme-600 dark:text-zinc-400"
          >
            {{ column.author }}
          </p>
          <div
            class="prose max-w-none prose-theme dark:prose-invert"
            v-html="column.body"
          ></div>
        </section>
      </div>

      <footer
        class="mt-10 flex flex-col gap-3 border-t border-theme-200 pt-6 text-sm sm:flex-row sm:justify-between dark:border-zinc-700"
      >
        <Link
          v-if="viewModel.previousIssue"
          :href="viewModel.previousIssue.url"
          class="inline-flex items-center gap-1 text-theme-700 hover:underline dark:text-zinc-300"
        >
          <Icon name="chevron-left" class="size-4" />
          {{ viewModel.previousIssue.title }}
        </Link>
        <span v-else></span>
        <Link
          v-if="viewModel.nextIssue"
          :href="viewModel.nextIssue.url"
          class="inline-flex items-center gap-1 text-theme-700 hover:underline dark:text-zinc-300"
        >
          {{ viewModel.nextIssue.title }}
          <Icon name="chevron-right" class="size-4" />
        </Link>
      </footer>
    </article>
  </AppLayout>
</template>
