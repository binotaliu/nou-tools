<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import { formatNewsletterDate } from '../../Composables/useNewsletterDates'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const issues = computed(() => props.viewModel.issues.data ?? [])

const jsonLd = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'ItemList',
  name: props.viewModel.title,
  itemListElement: issues.value.map((issue, index) => ({
    '@type': 'ListItem',
    position: index + 1,
    url: issue.url,
    name: issue.title,
  })),
}))
</script>

<template>
  <Head :title="`${viewModel.title} - NOU 小幫手`">
    <meta
      name="description"
      content="每兩週整理一次空大各處室、學系與學習指導中心的公告，以及接下來兩週的校曆重點。"
    />
    <link
      rel="alternate"
      type="application/atom+xml"
      :title="viewModel.title"
      :href="viewModel.feedUrl"
    />
    <script type="application/ld+json">
      {{ JSON.stringify(jsonLd) }}
    </script>
  </Head>

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-6">
      <header class="space-y-3">
        <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          {{ viewModel.title }}
        </h2>
        <p class="text-theme-700 dark:text-zinc-300">
          學校的公告散落在各處室、學系與學習指導中心的網站上。雙週報每兩週整理一次，隔週一發刊，告訴你接下來兩週要注意什麼、最近有哪些新消息。
        </p>
        <a
          :href="viewModel.feedUrl"
          class="inline-flex items-center gap-1 text-sm text-theme-600 underline hover:text-theme-900 hover:no-underline dark:text-zinc-400 dark:hover:text-zinc-100"
        >
          <Icon name="rss" class="size-4" />
          Atom 訂閱
        </a>
      </header>

      <ol
        v-if="issues.length > 0"
        class="divide-y divide-theme-200 rounded-lg border border-theme-200 bg-white shadow-sm dark:divide-zinc-700 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="newsletter-issue-list"
      >
        <li v-for="issue in issues" :key="issue.issueKey">
          <Link
            :href="issue.url"
            class="flex flex-col gap-1 p-4 transition hover:bg-theme-50 sm:flex-row sm:items-center sm:justify-between dark:hover:bg-zinc-800"
          >
            <span class="font-semibold text-theme-900 dark:text-zinc-100">
              {{ issue.title }}
            </span>
            <span class="text-sm text-theme-600 dark:text-zinc-400">
              {{ formatNewsletterDate(issue.publishesOn) }} 發刊
            </span>
          </Link>
        </li>
      </ol>

      <p
        v-else
        class="rounded-lg border border-theme-200 bg-white p-6 text-center text-theme-600 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400"
      >
        第一期正在準備中，敬請期待。
      </p>

      <nav
        v-if="viewModel.issues.last_page > 1"
        class="flex justify-between text-sm"
      >
        <Link
          v-if="viewModel.issues.prev_page_url"
          :href="viewModel.issues.prev_page_url"
          class="text-theme-700 underline hover:no-underline dark:text-zinc-300"
        >
          較新的期數
        </Link>
        <span v-else></span>
        <Link
          v-if="viewModel.issues.next_page_url"
          :href="viewModel.issues.next_page_url"
          class="text-theme-700 underline hover:no-underline dark:text-zinc-300"
        >
          較舊的期數
        </Link>
      </nav>
    </div>
  </AppLayout>
</template>
