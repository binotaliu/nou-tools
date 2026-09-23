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
import ShareButton from '../../Components/ShareButton.vue'
import HighlightsCalendar from '../../Components/Newsletter/HighlightsCalendar.vue'
import useMarkdownContainers from '../../Composables/useMarkdownContainers'
import useNewsletterReactions from '../../Composables/useNewsletterReactions'
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

// Jump links to each section. Only sections that render appear, and columns
// get an id from the column record since their titles are editor-written.
const tableOfContents = computed(() => {
  const entries = []

  if (issue.value.highlightsIntro) {
    entries.push({ id: 'newsletter-preface', label: '前言' })
  }
  if (issue.value.highlightEvents.length > 0) {
    entries.push({ id: 'newsletter-highlights', label: '本期行事曆' })
  }
  if (issue.value.newsItems.length > 0) {
    entries.push({ id: 'newsletter-news', label: '空大新消息' })
  }
  if (issue.value.artItems.length > 0) {
    entries.push({ id: 'newsletter-arts', label: '藝文活動' })
  }
  if (centerGroups.value.length > 0) {
    entries.push({ id: 'newsletter-centers', label: '各中心消息' })
  }
  issue.value.columns.forEach(column => {
    entries.push({ id: `newsletter-column-${column.id}`, label: column.title })
  })

  return entries
})

const {
  options: reactionOptions,
  mine: myReaction,
  pending: reactionPending,
  react,
} = useNewsletterReactions(() => props.viewModel)

const contentRoot = ref(null)

useMarkdownContainers(contentRoot, [() => props.viewModel.issue])
</script>

<template>
  <Head :title="pageTitle" />

  <AppLayout>
    <article
      class="mx-auto max-w-3xl rounded-lg border border-theme-200 bg-white p-6 shadow-sm sm:p-8 dark:border-zinc-700 dark:bg-zinc-900"
    >
      <figure v-if="issue.coverImageUrl" class="mb-6">
        <img
          :src="issue.coverImageUrl"
          :alt="issue.title"
          class="aspect-[3/1] w-full rounded-lg object-cover"
          data-testid="newsletter-cover-image"
        />
        <figcaption
          v-if="issue.coverImageCreditName"
          class="mt-1 text-right text-xs text-theme-700 dark:text-zinc-400"
          data-testid="newsletter-cover-image-credit"
        >
          Photo by
          <a
            :href="issue.coverImageCreditUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="underline hover:no-underline"
          >
            {{ issue.coverImageCreditName }}
          </a>
          on
          <a
            href="https://unsplash.com/?utm_source=nou-tools&utm_medium=referral"
            target="_blank"
            rel="noopener noreferrer"
            class="underline hover:no-underline"
          >
            Unsplash
          </a>
        </figcaption>
      </figure>

      <header
        class="mb-6 space-y-2 border-b border-theme-200 pb-6 dark:border-zinc-700"
      >
        <p class="text-sm text-theme-700 dark:text-zinc-400">
          <Link href="/newsletter" class="underline hover:no-underline">
            {{ viewModel.newsletterTitle }}
          </Link>
          ・{{ issue.issueKey }}
        </p>
        <h1 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          {{ issue.title }}
        </h1>
        <p class="text-sm text-theme-700 dark:text-zinc-400">
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
        <div
          v-else
          class="flex items-center justify-between gap-4 pt-1 text-sm text-theme-700 dark:text-zinc-400"
        >
          <span
            class="inline-flex items-center gap-1"
            data-testid="newsletter-view-count"
          >
            <Icon name="eye" class="size-4" />
            {{ viewModel.viewCount.toLocaleString() }} 次瀏覽
          </span>
          <ShareButton
            :title="issue.title"
            :url="viewModel.shareUrl"
            dialog-title="分享這期雙週報"
            input-label="雙週報連結"
            test-id-prefix="newsletter-share"
          />
        </div>
      </header>

      <nav
        v-if="tableOfContents.length > 1"
        class="mb-8 rounded-lg bg-theme-50 p-4 dark:bg-zinc-800"
        aria-labelledby="newsletter-toc-title"
        data-testid="newsletter-toc"
      >
        <h2
          id="newsletter-toc-title"
          class="mb-2 text-sm font-semibold text-theme-700 dark:text-zinc-300"
        >
          本期內容
        </h2>
        <ol class="flex flex-wrap gap-x-4 gap-y-1.5">
          <li v-for="entry in tableOfContents" :key="entry.id">
            <a
              :href="`#${entry.id}`"
              class="text-theme-800 underline decoration-theme-300 underline-offset-4 hover:decoration-theme-700 dark:text-zinc-200 dark:decoration-zinc-600 dark:hover:decoration-zinc-300"
              data-testid="newsletter-toc-link"
            >
              {{ entry.label }}
            </a>
          </li>
        </ol>
      </nav>

      <div
        ref="contentRoot"
        class="divide-y divide-theme-200 dark:divide-zinc-700 [&_h2]:scroll-mt-[6.45rem] [&>section]:py-8 [&>section:first-child]:pt-0 [&>section:last-child]:pb-0"
      >
        <section
          v-if="issue.highlightsIntro"
          aria-labelledby="newsletter-preface"
          data-testid="newsletter-preface"
        >
          <h2
            id="newsletter-preface"
            class="mb-3 flex items-center gap-2 text-2xl font-bold text-theme-700 dark:text-theme-300"
          >
            <Icon name="chat-bubble-left" class="size-6" />
            前言
          </h2>
          <div
            class="prose max-w-none prose-theme dark:prose-invert"
            v-html="issue.highlightsIntro"
          ></div>
        </section>

        <section
          v-if="issue.highlightEvents.length > 0"
          aria-labelledby="newsletter-highlights"
          data-testid="newsletter-highlights"
        >
          <h2
            id="newsletter-highlights"
            class="mb-3 flex items-center gap-2 text-2xl font-bold text-theme-700 dark:text-theme-300"
          >
            <Icon name="calendar-days" class="size-6" />
            本期行事曆
          </h2>
          <HighlightsCalendar
            :highlights-from="issue.highlightsFrom"
            :highlights-to="issue.highlightsTo"
            :events="issue.highlightEvents"
          />
          <ol
            class="mt-4 space-y-2 rounded-lg bg-theme-50 p-4 dark:bg-zinc-800"
          >
            <li
              v-for="(event, index) in issue.highlightEvents"
              :key="`${event.startDate}-${event.name}`"
              class="flex items-start gap-x-3"
            >
              <span
                class="inline-flex size-5 shrink-0 items-center justify-center rounded bg-theme-200 text-xs font-medium text-theme-900 sm:hidden dark:bg-theme-800/70 dark:text-theme-100"
                aria-hidden="true"
              >
                {{ index + 1 }}
              </span>
              <div class="flex min-w-0 flex-col gap-x-3 sm:flex-row">
                <span
                  class="shrink-0 font-mono text-sm text-theme-700 sm:w-44 dark:text-zinc-300"
                >
                  {{
                    formatNewsletterDateRange(event.startDate, event.endDate)
                  }}
                </span>
                <span class="text-theme-900 dark:text-zinc-100">
                  {{ event.name }}
                  <span
                    v-if="event.description"
                    class="block text-sm text-theme-700 dark:text-zinc-400"
                  >
                    {{ event.description }}
                  </span>
                </span>
              </div>
            </li>
          </ol>
        </section>

        <section
          v-if="issue.newsItems.length > 0"
          aria-labelledby="newsletter-news"
          data-testid="newsletter-news"
        >
          <h2
            id="newsletter-news"
            class="mb-4 flex items-center gap-2 text-2xl font-bold text-theme-700 dark:text-theme-300"
          >
            <Icon name="megaphone" class="size-6" />
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
                    class="inline size-4 align-baseline text-theme-700"
                  />
                </a>
                <template v-else>{{ item.headline }}</template>
              </h3>
              <p class="text-sm text-theme-700 dark:text-zinc-400">
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
          v-if="issue.artItems.length > 0"
          aria-labelledby="newsletter-arts"
          data-testid="newsletter-arts"
        >
          <h2
            id="newsletter-arts"
            class="mb-4 flex items-center gap-2 text-2xl font-bold text-theme-700 dark:text-theme-300"
          >
            <Icon name="paint-brush" class="size-6" />
            藝文活動
          </h2>
          <ul class="space-y-5">
            <li v-for="item in issue.artItems" :key="item.id">
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
                    class="inline size-4 align-baseline text-theme-700"
                  />
                </a>
                <template v-else>{{ item.headline }}</template>
              </h3>
              <p class="text-sm text-theme-700 dark:text-zinc-400">
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
            class="mb-4 flex items-center gap-2 text-2xl font-bold text-theme-700 dark:text-theme-300"
          >
            <Icon name="building-storefront" class="size-6" />
            各中心消息
          </h2>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div
              v-for="group in centerGroups"
              :key="group.sourceName"
              class="rounded-lg border border-theme-200 p-4 dark:border-zinc-700"
              data-testid="newsletter-center"
            >
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
                      class="inline size-4 align-baseline text-theme-700"
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
          :aria-labelledby="`newsletter-column-${column.id}`"
          data-testid="newsletter-column"
        >
          <h2
            :id="`newsletter-column-${column.id}`"
            class="flex items-center gap-2 text-2xl font-bold text-theme-700 dark:text-theme-300"
          >
            <Icon name="pencil-square" class="size-6" />
            {{ column.title }}
          </h2>
          <p
            v-if="column.author"
            class="mb-3 text-sm text-theme-700 dark:text-zinc-400"
          >
            {{ column.author }}
          </p>
          <div
            class="prose max-w-none prose-theme dark:prose-invert"
            v-html="column.body"
          ></div>
        </section>
      </div>

      <section
        v-if="issue.isPublished"
        class="mt-10 border-t border-theme-200 pt-6 text-center dark:border-zinc-700"
        aria-labelledby="newsletter-reactions"
        data-testid="newsletter-reactions"
      >
        <h2
          id="newsletter-reactions"
          class="mb-3 text-base font-semibold text-theme-800 dark:text-zinc-200"
        >
          這期雙週報，你覺得怎麼樣？
        </h2>
        <div class="flex flex-wrap justify-center gap-2">
          <button
            v-for="option in reactionOptions"
            :key="option.key"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm transition disabled:opacity-60"
            :class="
              myReaction === option.key
                ? 'border-theme-500 bg-theme-100 font-semibold text-theme-900 dark:border-theme-400 dark:bg-theme-900/50 dark:text-zinc-100'
                : 'border-theme-200 bg-white text-theme-700 hover:bg-theme-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800'
            "
            :aria-pressed="myReaction === option.key"
            :disabled="reactionPending"
            :data-testid="`newsletter-reaction-${option.key}`"
            @click="react(option.key)"
          >
            <span aria-hidden="true">{{ option.emoji }}</span>
            {{ option.label }}
            <span
              class="tabular-nums"
              data-testid="newsletter-reaction-count"
              >{{ option.count }}</span
            >
          </button>
        </div>
        <div class="mt-4 flex items-center justify-center gap-3">
          <span class="text-sm text-theme-700 dark:text-zinc-400">
            覺得有用？分享給同學吧
          </span>
          <ShareButton
            :title="issue.title"
            :url="viewModel.shareUrl"
            dialog-title="分享這期雙週報"
            input-label="雙週報連結"
            test-id-prefix="newsletter-share-end"
          />
        </div>
      </section>

      <footer
        v-if="viewModel.previousIssue || viewModel.nextIssue"
        class="mt-6 flex flex-col gap-3 border-t border-theme-200 pt-6 text-sm sm:flex-row sm:justify-between dark:border-zinc-700"
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

      <!-- License Footer -->
      <div
        class="mt-8 flex items-start gap-3 border-t border-theme-200 pt-6 text-sm text-theme-700 dark:border-zinc-700 dark:text-zinc-400"
        data-testid="newsletter-license"
      >
        <Icon name="information-circle" class="mt-0.5 size-5 shrink-0" />
        <div class="space-y-1">
          <p>
            本期雙週報的原創內容（前言、專欄及編輯撰寫的摘要）採用
            <a
              href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh-hant"
              target="_blank"
              rel="noopener noreferrer"
              class="text-theme-700 underline transition hover:text-theme-900 hover:no-underline dark:text-zinc-300 dark:hover:text-zinc-100"
            >
              創用 CC 姓名標示─非商業性─相同方式分享 4.0 國際版授權條款 (CC
              BY-NC-SA 4.0)
            </a>
            釋出。
          </p>
          <p>
            所引用的各單位公告與連結內容、封面照片，著作權仍屬原作者或原發布單位，不適用前述授權。
          </p>
        </div>
      </div>
    </article>
  </AppLayout>
</template>
