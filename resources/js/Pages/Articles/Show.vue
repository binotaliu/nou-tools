<script setup>
// The share button and modal are driven by the `useArticleShare`
// composable. `viewModel.article.type` only survives
// Inertia's JSON serialization as the enum's string value (`kb` / `manual`),
// so `App\Enums\ArticleType::label()` is re-implemented here from that
// value. `viewModel.article.content` / `viewModel.sidebarContent` are plain
// HTML (rendered Markdown) straight from the ViewModel, rendered with
// v-html.
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import useArticleShare from '../../Composables/useArticleShare'
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

const shareInput = ref(null)

const { showShareModal, copied, shareUrl, share, copy } = useArticleShare(
  {
    shareTitle: props.viewModel.article.title,
    shareUrl: currentUrl.value,
  },
  shareInput
)

function handleEscape(event) {
  if (event.key === 'Escape') {
    showShareModal.value = false
  }
}

onMounted(() => window.addEventListener('keydown', handleEscape))
onUnmounted(() => window.removeEventListener('keydown', handleEscape))

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
              class="prose prose-sm max-w-none prose-theme dark:prose-invert"
              v-html="viewModel.sidebarContent"
            ></nav>

            <div
              class="mt-4 border-t border-theme-200 pt-4 dark:border-zinc-700"
            >
              <Link
                :href="indexUrl"
                class="inline-flex items-center gap-1 text-sm text-theme-600 transition-colors hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100"
              >
                <Icon name="chevron-left" class="size-3" />
                回到{{ typeLabel }}首頁
              </Link>
            </div>
          </div>
        </aside>

        <!-- Main Content -->
        <main class="min-w-0 flex-1">
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

                <button
                  type="button"
                  class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-theme-200 bg-white px-3 py-1 text-sm font-semibold text-theme-900 transition hover:bg-theme-50 disabled:border-theme-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800 dark:disabled:border-zinc-800"
                  data-testid="article-share-button"
                  @click="share()"
                >
                  <Icon name="share" class="size-4" />
                  分享
                </button>
              </div>

              <div
                class="flex items-center gap-4 text-sm text-theme-500 dark:text-zinc-400"
              >
                <span>作者：{{ viewModel.article.author }}</span>
                <span
                  >發表於：{{ formatDate(viewModel.article.publishedAt) }}</span
                >
                <span v-if="viewModel.article.updatedAt">
                  更新於：{{ formatDate(viewModel.article.updatedAt) }}
                </span>
              </div>

              <Teleport to="body">
                <div
                  v-if="showShareModal"
                  class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:items-center sm:p-0"
                >
                  <div
                    class="fixed inset-0 bg-black/40"
                    aria-hidden="true"
                    @click="showShareModal = false"
                  ></div>

                  <div
                    role="dialog"
                    aria-modal="true"
                    data-testid="article-share-modal"
                    class="relative max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto rounded-lg bg-white p-6 shadow-lg sm:max-h-[calc(100vh-2rem)] dark:bg-zinc-900"
                    @click.self="showShareModal = false"
                  >
                    <h3
                      class="mb-2 text-lg font-semibold text-theme-900 dark:text-zinc-100"
                    >
                      分享這篇文章
                    </h3>

                    <div
                      class="flex items-stretch gap-3 rounded border border-theme-300 bg-white text-sm text-theme-600 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-400"
                    >
                      <input
                        ref="shareInput"
                        class="flex-1 px-3 py-2 font-mono break-all text-theme-600 dark:text-zinc-400"
                        :value="shareUrl"
                        readonly
                        aria-label="文章連結"
                        @click="$event.target.select()"
                      />

                      <button
                        type="button"
                        class="my-1 mr-1 inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-theme-200 bg-theme-200 px-3 py-1 text-sm font-semibold whitespace-nowrap text-theme-900 transition hover:bg-theme-300 disabled:bg-theme-100 dark:border-zinc-700 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600 dark:disabled:bg-zinc-900"
                        :aria-pressed="copied.toString()"
                        data-testid="article-share-copy"
                        @click="copy()"
                      >
                        <span v-show="!copied">
                          <Icon
                            name="clipboard-document"
                            class="inline size-4"
                          />
                          複製連結
                        </span>
                        <span v-show="copied">
                          <Icon name="check" class="inline size-4" />
                          已複製！
                        </span>
                      </button>
                    </div>

                    <div class="mt-4 flex justify-end">
                      <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 text-sm font-semibold text-theme-900 transition hover:bg-theme-50 disabled:border-theme-200 disabled:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800 dark:disabled:border-zinc-700 dark:disabled:bg-zinc-950"
                        data-testid="article-share-close"
                        @click="showShareModal = false"
                      >
                        關閉
                      </button>
                    </div>
                  </div>
                </div>
              </Teleport>
            </header>

            <!-- Article Content -->
            <div
              ref="articleContentRoot"
              class="prose max-w-none prose-theme dark:prose-zinc dark:prose-invert"
              v-html="viewModel.article.content"
            ></div>

            <!-- License Footer -->
            <footer
              class="mt-8 border-t border-theme-200 pt-6 dark:border-zinc-700"
            >
              <div
                class="flex items-center gap-3 text-sm text-theme-600 dark:text-zinc-400"
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
        </main>
      </div>
    </div>
  </AppLayout>
</template>
