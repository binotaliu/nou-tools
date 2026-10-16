<script setup>
// The wiki's homepage, after Wikipedia's: the intro is the protected
// `main-page` page (admins edit it in Filament), everything under it keeps
// itself up to date. The full A-Z list lives on 所有條目 (/wiki/all).
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Button from '../../Components/Button.vue'
import Icon from '../../Components/Icon.vue'
import useMarkdownContainers from '../../Composables/useMarkdownContainers'
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

const introRoot = ref(null)

useMarkdownContainers(introRoot, [() => props.viewModel.intro])

const panelClasses =
  'rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900'
const headingClasses =
  'mb-4 flex items-center gap-2 text-lg font-semibold text-theme-900 dark:text-zinc-100'
const pageLinkClasses =
  'font-medium text-theme-900 underline hover:no-underline dark:text-zinc-100'
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
          NOU
          小幫手知識庫是一個自由且開放的維基知識庫，任何人都可以參與編輯。目前共有
          <strong data-testid="wiki-page-count">{{
            viewModel.pageCount
          }}</strong>
          個條目。
        </p>
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

      <div class="flex flex-wrap gap-2">
        <Link
          :href="viewModel.newPageUrl"
          :class="WIKI_PRIMARY_LINK"
          data-testid="wiki-new-page"
        >
          <Icon name="squares-plus" class="size-4" />
          建立新條目
        </Link>
        <Link
          :href="viewModel.allPagesUrl"
          :class="WIKI_OUTLINE_LINK"
          data-testid="wiki-all-pages-link"
        >
          <Icon name="book-open" class="size-4" />
          所有條目
        </Link>
      </div>

      <section
        v-if="viewModel.searchResults !== null"
        aria-labelledby="wiki-search-results"
        :class="panelClasses"
        data-testid="wiki-search-results"
      >
        <h3 id="wiki-search-results" :class="headingClasses">
          「{{ viewModel.query }}」的搜尋結果
        </h3>
        <ul v-if="viewModel.searchResults.length" class="space-y-3">
          <li v-for="page in viewModel.searchResults" :key="page.slug">
            <Link :href="page.url" :class="pageLinkClasses">{{
              page.title
            }}</Link>
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

      <section
        v-if="viewModel.intro"
        aria-label="知識庫介紹"
        :class="panelClasses"
        data-testid="wiki-intro"
      >
        <div
          ref="introRoot"
          class="prose max-w-none prose-theme dark:prose-invert"
          v-html="viewModel.intro"
        ></div>
      </section>

      <p
        v-if="viewModel.pageCount === 0"
        :class="panelClasses"
        class="text-theme-800 dark:text-zinc-300"
      >
        還沒有任何條目，歡迎建立第一個。
      </p>

      <div v-else class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
          <section
            v-if="viewModel.featured"
            aria-labelledby="wiki-featured"
            :class="panelClasses"
            data-testid="wiki-featured"
          >
            <h3 id="wiki-featured" :class="headingClasses">
              <Icon name="sparkles" class="size-5" />
              {{ viewModel.featured.isFeatured ? '今日精選' : '今日條目' }}
            </h3>
            <Link
              :href="viewModel.featured.page.url"
              class="text-xl font-bold text-theme-900 underline hover:no-underline dark:text-zinc-100"
              >{{ viewModel.featured.page.title }}</Link
            >
            <p
              v-if="viewModel.featured.page.description"
              class="mt-1 text-theme-700 dark:text-zinc-400"
            >
              {{ viewModel.featured.page.description }}
            </p>
            <p
              v-if="viewModel.featured.excerpt"
              class="mt-3 text-theme-800 dark:text-zinc-300"
            >
              {{ viewModel.featured.excerpt }}
            </p>
            <div class="mt-4 flex flex-wrap items-center gap-3">
              <Link
                :href="viewModel.featured.page.url"
                class="inline-flex items-center gap-1 text-sm font-semibold text-theme-900 underline hover:no-underline dark:text-zinc-100"
              >
                繼續閱讀
                <Icon name="chevron-right" class="size-3" />
              </Link>
              <Link
                v-for="category in viewModel.featured.categories"
                :key="category.name"
                :href="category.url"
                class="rounded-full border border-theme-300 px-2 py-0.5 text-xs text-theme-800 hover:bg-theme-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                >{{ category.name }}</Link
              >
            </div>
          </section>

          <section
            v-if="viewModel.categories.length"
            aria-labelledby="wiki-categories"
            data-testid="wiki-categories"
          >
            <h3 id="wiki-categories" :class="headingClasses">
              <Icon name="tag" class="size-5" />
              分類瀏覽
            </h3>
            <ul class="grid gap-4 sm:grid-cols-2">
              <li
                v-for="category in viewModel.categories"
                :key="category.name"
                :class="panelClasses"
                class="flex flex-col p-5!"
              >
                <Link
                  :href="category.url"
                  class="font-semibold text-theme-900 underline hover:no-underline dark:text-zinc-100"
                  >{{ category.name }}</Link
                >
                <ul class="mt-2 flex-1 space-y-1 text-sm">
                  <li v-for="page in category.pages" :key="page.slug">
                    <Link
                      :href="page.url"
                      class="text-theme-800 hover:underline dark:text-zinc-300"
                      >{{ page.title }}</Link
                    >
                  </li>
                </ul>
                <Link
                  v-if="category.pageCount > category.pages.length"
                  :href="category.url"
                  class="mt-3 text-sm text-theme-700 underline hover:no-underline dark:text-zinc-400"
                  >全部 {{ category.pageCount }} 個條目</Link
                >
              </li>
            </ul>
          </section>
        </div>

        <div class="space-y-6">
          <section
            v-if="viewModel.recentChanges.length"
            aria-labelledby="wiki-recent-changes"
            :class="panelClasses"
          >
            <h3 id="wiki-recent-changes" :class="headingClasses">
              <Icon name="clock" class="size-5" />
              最近更新
            </h3>
            <ul class="space-y-3 text-sm" data-testid="wiki-recent-changes">
              <li v-for="change in viewModel.recentChanges" :key="change.id">
                <Link :href="change.url" :class="pageLinkClasses">{{
                  change.title
                }}</Link>
                <p class="text-theme-700 dark:text-zinc-400">
                  {{ change.editSummary || change.originLabel }} ·
                  {{ formatWikiDate(change.createdAt) }}
                </p>
              </li>
            </ul>
          </section>

          <section
            v-if="viewModel.newPages.length"
            aria-labelledby="wiki-new-pages"
            :class="panelClasses"
          >
            <h3 id="wiki-new-pages" :class="headingClasses">
              <Icon name="plus" class="size-5" />
              新條目
            </h3>
            <ul class="space-y-2 text-sm" data-testid="wiki-new-pages">
              <li v-for="page in viewModel.newPages" :key="page.slug">
                <Link :href="page.url" :class="pageLinkClasses">{{
                  page.title
                }}</Link>
              </li>
            </ul>
          </section>

          <section
            v-if="viewModel.wantedPages.length"
            aria-labelledby="wiki-wanted-pages"
            :class="panelClasses"
          >
            <h3 id="wiki-wanted-pages" :class="headingClasses">
              <Icon name="pencil-square" class="size-5" />
              等你來寫
            </h3>
            <p class="mb-3 text-sm text-theme-700 dark:text-zinc-400">
              其他條目提到了這些主題，但還沒有人寫。
            </p>
            <ul class="space-y-2 text-sm" data-testid="wiki-wanted-pages">
              <li v-for="wanted in viewModel.wantedPages" :key="wanted.slug">
                <Link
                  :href="wanted.createUrl"
                  class="font-mono text-red-700 underline hover:no-underline dark:text-red-400"
                  >{{ wanted.slug }}</Link
                >
                <span class="text-theme-700 dark:text-zinc-400">
                  · {{ wanted.linkCount }} 個條目連結</span
                >
              </li>
            </ul>
          </section>
        </div>
      </div>

      <p
        v-if="viewModel.introHistoryUrl"
        class="text-right text-xs text-theme-700 dark:text-zinc-400"
      >
        <Link
          :href="viewModel.introHistoryUrl"
          class="underline hover:no-underline"
          >首頁介紹的編輯歷史</Link
        >
      </p>
    </div>
  </AppLayout>
</template>
