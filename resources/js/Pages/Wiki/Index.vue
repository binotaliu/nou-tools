<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Button from '../../Components/Button.vue'
import Icon from '../../Components/Icon.vue'
import { formatWikiDate } from '../../Composables/useWikiDates'
import {
  WIKI_OUTLINE_LINK,
  WIKI_PRIMARY_LINK,
} from '../../Components/Wiki/linkClasses'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const query = ref(props.viewModel.query)

function search() {
  router.get(
    '/wiki',
    query.value.trim() === '' ? {} : { q: query.value.trim() },
    { preserveState: true, preserveScroll: true }
  )
}
</script>

<template>
  <Head :title="`${viewModel.title} - NOU 小幫手`" />

  <AppLayout>
    <div class="mx-auto max-w-5xl space-y-6">
      <header class="space-y-3">
        <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          {{ viewModel.title }}
        </h2>
        <p class="text-theme-800 dark:text-zinc-300">
          由空大同學一起整理的知識庫。任何人都可以提出修改：說明想改什麼、附上來源，AI
          編輯會依照<Link
            :href="viewModel.writingGuideUrl"
            class="underline hover:no-underline"
            >撰寫指南</Link
          >改寫條目，確認後送出。內容僅供參考，請以學校公告為準。
        </p>
        <div class="flex flex-wrap gap-2">
          <Link
            :href="viewModel.newPageUrl"
            :class="WIKI_PRIMARY_LINK"
            data-testid="wiki-new-page"
          >
            <Icon name="squares-plus" class="size-4" />
            建立新條目
          </Link>
          <Link :href="viewModel.syntaxGuideUrl" :class="WIKI_OUTLINE_LINK">
            <Icon name="code-bracket" class="size-4" />
            語法說明
          </Link>
        </div>
      </header>

      <form
        role="search"
        class="flex gap-2"
        data-testid="wiki-search"
        @submit.prevent="search"
      >
        <label for="wiki-search-input" class="sr-only">搜尋條目</label>
        <input
          id="wiki-search-input"
          v-model="query"
          type="search"
          accesskey="8"
          placeholder="搜尋條目，例如：考試、學分抵免"
          class="min-w-0 flex-1 rounded-md border border-theme-300 bg-white px-3 py-2 text-theme-900 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
        />
        <Button type="submit" variant="primary">
          <Icon name="magnifying-glass" class="size-4" />
          搜尋
        </Button>
      </form>

      <section
        v-if="viewModel.searchResults !== null"
        aria-labelledby="wiki-search-results"
        class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="wiki-search-results"
      >
        <h3
          id="wiki-search-results"
          class="mb-4 text-lg font-semibold text-theme-900 dark:text-zinc-100"
        >
          「{{ viewModel.query }}」的搜尋結果
        </h3>
        <ul v-if="viewModel.searchResults.length" class="space-y-3">
          <li v-for="page in viewModel.searchResults" :key="page.slug">
            <Link
              :href="page.url"
              class="font-medium text-theme-900 underline hover:no-underline dark:text-zinc-100"
              >{{ page.title }}</Link
            >
            <p
              v-if="page.description"
              class="text-sm text-theme-700 dark:text-zinc-400"
            >
              {{ page.description }}
            </p>
          </li>
        </ul>
        <p v-else class="text-theme-800 dark:text-zinc-300">
          找不到相關條目。
          <Link
            :href="`${viewModel.newPageUrl}?title=${encodeURIComponent(viewModel.query)}`"
            class="underline hover:no-underline"
            >建立「{{ viewModel.query }}」條目</Link
          >
        </p>
      </section>

      <div class="grid gap-6 md:grid-cols-3">
        <section
          aria-labelledby="wiki-all-pages"
          class="rounded-lg border border-theme-200 bg-white p-6 md:col-span-2 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <h3
            id="wiki-all-pages"
            class="mb-4 text-lg font-semibold text-theme-900 dark:text-zinc-100"
          >
            所有條目
          </h3>
          <ul
            v-if="viewModel.pages.length"
            class="space-y-3"
            data-testid="wiki-pages"
          >
            <li v-for="page in viewModel.pages" :key="page.slug">
              <Link
                :href="page.url"
                class="font-medium text-theme-900 underline hover:no-underline dark:text-zinc-100"
                >{{ page.title }}</Link
              >
              <p
                v-if="page.description"
                class="text-sm text-theme-700 dark:text-zinc-400"
              >
                {{ page.description }}
              </p>
            </li>
          </ul>
          <p v-else class="text-theme-800 dark:text-zinc-300">
            還沒有任何條目，歡迎建立第一個。
          </p>
        </section>

        <div class="space-y-6">
          <section
            v-if="viewModel.categories.length"
            aria-labelledby="wiki-categories"
            class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <h3
              id="wiki-categories"
              class="mb-4 text-lg font-semibold text-theme-900 dark:text-zinc-100"
            >
              分類
            </h3>
            <ul class="flex flex-wrap gap-2">
              <li v-for="category in viewModel.categories" :key="category.name">
                <Link
                  :href="category.url"
                  class="inline-flex items-center gap-1 rounded-full border border-theme-300 px-3 py-1 text-sm text-theme-900 hover:bg-theme-50 dark:border-zinc-600 dark:text-zinc-100 dark:hover:bg-zinc-800"
                >
                  {{ category.name }}
                  <span class="text-theme-700 dark:text-zinc-400"
                    >({{ category.pageCount }})</span
                  >
                </Link>
              </li>
            </ul>
          </section>

          <section
            v-if="viewModel.recentChanges.length"
            aria-labelledby="wiki-recent-changes"
            class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <h3
              id="wiki-recent-changes"
              class="mb-4 text-lg font-semibold text-theme-900 dark:text-zinc-100"
            >
              最近更新
            </h3>
            <ul class="space-y-3 text-sm" data-testid="wiki-recent-changes">
              <li v-for="change in viewModel.recentChanges" :key="change.id">
                <Link
                  :href="change.url"
                  class="font-medium text-theme-900 underline hover:no-underline dark:text-zinc-100"
                  >{{ change.title }}</Link
                >
                <p class="text-theme-700 dark:text-zinc-400">
                  {{ change.editSummary || change.originLabel }} ·
                  {{ formatWikiDate(change.createdAt) }}
                </p>
              </li>
            </ul>
          </section>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
