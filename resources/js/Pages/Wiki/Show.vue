<script setup>
// `viewModel.page.content` is the rendered page (plain HTML from the
// ViewModel), mounted with v-html and hydrated by useMarkdownContainers,
// which watches it: Inertia reuses this component between pages.
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import ShareButton from '../../Components/ShareButton.vue'
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

const page = computed(() => props.viewModel.page)

const contentRoot = ref(null)

useMarkdownContainers(contentRoot, [() => props.viewModel.page.content])
</script>

<template>
  <Head :title="`${page.title} - ${viewModel.wikiTitle} - NOU 小幫手`" />

  <AppLayout>
    <div class="mx-auto max-w-4xl space-y-6">
      <nav aria-label="麵包屑" class="text-sm">
        <Link
          href="/wiki"
          class="inline-flex items-center gap-1 text-theme-700 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100"
        >
          <Icon name="chevron-left" class="size-3" />
          {{ viewModel.wikiTitle }}
        </Link>
      </nav>

      <article
        class="rounded-lg border border-theme-200 bg-white p-6 shadow-sm sm:p-8 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <header
          class="mb-6 space-y-3 border-b border-theme-200 pb-6 dark:border-zinc-700"
        >
          <div class="flex items-start justify-between gap-4">
            <h1
              class="text-3xl font-bold text-theme-900 dark:text-zinc-100"
              data-testid="wiki-page-title"
            >
              {{ page.title }}
            </h1>
            <ShareButton
              :title="page.title"
              :url="page.url"
              dialog-title="分享這個條目"
              input-label="條目連結"
              test-id-prefix="wiki-share"
            />
          </div>
          <p
            class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-theme-700 dark:text-zinc-400"
          >
            <span>最後更新：{{ formatWikiDate(page.updatedAt) }}</span>
            <span
              v-if="page.isProtected"
              class="inline-flex items-center gap-1"
            >
              <Icon name="lock-closed" class="size-4" />
              受保護的條目
            </span>
          </p>
          <div class="flex flex-wrap gap-2">
            <Link
              v-if="!page.isProtected"
              :href="page.editUrl"
              :class="WIKI_PRIMARY_LINK"
              data-testid="wiki-edit"
            >
              <Icon name="pencil-square" class="size-4" />
              提出修改
            </Link>
            <Link :href="page.historyUrl" :class="WIKI_OUTLINE_LINK">
              <Icon name="clock" class="size-4" />
              編輯歷史
            </Link>
          </div>
        </header>

        <div
          ref="contentRoot"
          class="prose max-w-none prose-theme dark:prose-invert"
          data-testid="wiki-page-content"
          v-html="page.content"
        ></div>

        <footer
          class="mt-8 space-y-4 border-t border-theme-200 pt-6 text-sm text-theme-700 dark:border-zinc-700 dark:text-zinc-400"
        >
          <div v-if="page.categories.length" class="flex flex-wrap gap-2">
            <span class="font-medium">分類：</span>
            <Link
              v-for="category in page.categories"
              :key="category.name"
              :href="category.url"
              class="underline hover:no-underline"
              >{{ category.name }}</Link
            >
          </div>

          <div v-if="viewModel.backlinks.length">
            <h2 class="mb-1 font-medium">連結到此頁的條目</h2>
            <ul class="flex flex-wrap gap-x-4 gap-y-1">
              <li v-for="backlink in viewModel.backlinks" :key="backlink.slug">
                <Link
                  :href="backlink.url"
                  class="underline hover:no-underline"
                  >{{ backlink.title }}</Link
                >
              </li>
            </ul>
          </div>

          <p class="flex items-start gap-2">
            <Icon name="information-circle" class="size-5 shrink-0" />
            <span>
              本條目由社群提出修改、AI
              協助編輯，內容僅供參考，請以學校公告為準。採用
              <a
                href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh-hant"
                target="_blank"
                rel="noopener noreferrer"
                class="underline hover:no-underline"
                >創用 CC 姓名標示─非商業性─相同方式分享 4.0 國際版授權條款 (CC
                BY-NC-SA 4.0)</a
              >
              釋出。
            </span>
          </p>
        </footer>
      </article>
    </div>
  </AppLayout>
</template>
