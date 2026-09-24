<script setup>
// The share button and modal are the shared ShareButton component
// (driven by `useArticleShare`). `viewModel.article.type` only survives
// Inertia's JSON serialization as the enum's string value (`kb` / `manual`),
// so `App\Enums\ArticleType::label()` is re-implemented here from that
// value. `viewModel.article.content` / `viewModel.sidebarContent` are plain
// HTML (rendered Markdown) straight from the ViewModel, rendered with
// v-html.
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import ShareButton from '../../Components/ShareButton.vue'
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
  () =>
    ARTICLE_TYPE_LABELS[props.viewModel.article.type] ??
    props.viewModel.article.type
)

const pageTitle = computed(
  () => `${props.viewModel.article.title} - ${typeLabel.value} - NOU 小幫手`
)

const indexUrl = computed(() => `/${props.viewModel.article.type}`)

const currentUrl = computed(() =>
  typeof window !== 'undefined' ? window.location.href : ''
)

function formatDate(value) {
  if (!value) {
    return ''
  }

  const date = new Date(value)

  return `${date.getFullYear()} 年 ${String(date.getMonth() + 1).padStart(2, '0')} 月 ${String(date.getDate()).padStart(2, '0')} 日`
}

// Two separate v-html roots, hydrated independently — the sidebar is
// _sidebar.md and can carry the same containers the body can.
const articleContentRoot = ref(null)
const sidebarContentRoot = ref(null)

useMarkdownContainers(articleContentRoot, [
  () => props.viewModel.article.content,
])
useMarkdownContainers(sidebarContentRoot, [
  () => props.viewModel.sidebarContent,
])
</script>

<template>
  <Head :title="pageTitle" />

  <AppLayout>
    <div class="mx-auto max-w-7xl">
      <div class="flex flex-col gap-6 md:flex-row">
        <!-- Sidebar -->
        <aside class="shrink-0 md:w-64">
          <div
            class="sticky top-[6.45rem] rounded-lg border border-theme-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
          >
            <h3 class="mb-3 font-semibold text-theme-900 dark:text-zinc-100">
              {{ typeLabel }}
            </h3>

            <nav
              v-if="viewModel.sidebarContent"
              ref="sidebarContentRoot"
              :aria-label="typeLabel"
              class="prose prose-sm max-w-none prose-theme dark:prose-invert"
              v-html="viewModel.sidebarContent"
            ></nav>

            <div
              class="mt-4 border-t border-theme-200 pt-4 dark:border-zinc-700"
            >
              <Link
                :href="indexUrl"
                class="inline-flex items-center gap-1 text-sm text-theme-700 transition-colors hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100"
              >
                <Icon name="chevron-left" class="size-3" />
                回到{{ typeLabel }}首頁
              </Link>
            </div>
          </div>
        </aside>

        <!-- Main Content -->
        <div class="min-w-0 flex-1">
          <article
            class="rounded-lg border border-theme-200 bg-white p-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
          >
            <!-- Article Header -->
            <header
              class="mb-6 border-b border-theme-200 pb-6 dark:border-zinc-700"
            >
              <div class="mb-3 flex items-start justify-between gap-4">
                <h1
                  class="text-3xl font-bold text-theme-900 dark:text-zinc-100"
                >
                  {{ viewModel.article.title }}
                </h1>

                <ShareButton
                  :title="viewModel.article.title"
                  :url="currentUrl"
                  dialog-title="分享這篇文章"
                  input-label="文章連結"
                  test-id-prefix="article-share"
                />
              </div>

              <div
                class="flex items-center gap-4 text-sm text-theme-700 dark:text-zinc-400"
              >
                <span>作者：{{ viewModel.article.author }}</span>
                <span
                  >發表於：{{ formatDate(viewModel.article.publishedAt) }}</span
                >
                <span v-if="viewModel.article.updatedAt">
                  更新於：{{ formatDate(viewModel.article.updatedAt) }}
                </span>
              </div>
            </header>

            <!-- Article Content -->
            <div
              ref="articleContentRoot"
              class="prose max-w-none prose-theme dark:prose-invert"
              v-html="viewModel.article.content"
            ></div>

            <!-- License Footer -->
            <footer
              class="mt-8 border-t border-theme-200 pt-6 dark:border-zinc-700"
            >
              <div
                class="flex items-center gap-3 text-sm text-theme-700 dark:text-zinc-400"
              >
                <Icon name="information-circle" class="size-5 shrink-0" />

                <div>
                  <p
                    class="sr-only font-medium text-theme-700 dark:text-zinc-300"
                  >
                    授權方式
                  </p>
                  <p>
                    本文採用
                    <a
                      href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh-hant"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-theme-700 underline transition hover:text-theme-900 hover:no-underline dark:text-zinc-300 dark:hover:text-zinc-100"
                    >
                      創用 CC 姓名標示─非商業性─相同方式分享 4.0 國際版授權條款
                      (CC BY-NC-SA 4.0)
                    </a>
                    釋出。
                  </p>
                </div>
              </div>
            </footer>
          </article>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
