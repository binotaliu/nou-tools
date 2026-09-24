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
</script>

<template>
  <Head :title="`${viewModel.title} - NOU 小幫手`" />

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-6">
      <header class="space-y-3">
        <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          {{ viewModel.title }}
        </h2>
        <p class="text-theme-700 dark:text-zinc-300">
          本站發行的數位刊物，（預計）每兩週發行一次。
        </p>
        <a
          :href="viewModel.feedUrl"
          class="inline-flex items-center gap-1 text-sm text-theme-700 underline hover:text-theme-900 hover:no-underline dark:text-zinc-400 dark:hover:text-zinc-100"
        >
          <Icon name="rss" class="size-4" />
          Atom 訂閱
        </a>
      </header>

      <section v-if="viewModel.latestIssue" class="space-y-3">
        <h3 class="text-xl font-bold text-theme-900 dark:text-zinc-100">
          最新一期
        </h3>
        <Link
          :href="viewModel.latestIssue.url"
          class="group block overflow-hidden rounded-lg border border-theme-200 bg-white shadow-sm transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900"
          data-testid="newsletter-highlight"
        >
          <img
            v-if="viewModel.latestIssue.coverImageUrl"
            :src="viewModel.latestIssue.coverImageUrl"
            alt=""
            class="aspect-[3/1] w-full object-cover"
            data-testid="newsletter-cover-image"
          />
          <div
            v-else
            class="flex aspect-[3/1] w-full items-center justify-center bg-theme-100 text-theme-700 dark:bg-zinc-800 dark:text-zinc-600"
            data-testid="newsletter-cover-placeholder"
          >
            <Icon name="book-open" class="size-12" />
          </div>
          <div class="space-y-1 p-4">
            <p
              class="text-xl font-semibold text-theme-900 group-hover:underline dark:text-zinc-100"
            >
              {{ viewModel.latestIssue.title }}
            </p>
            <p class="text-sm text-theme-700 dark:text-zinc-400">
              {{ formatNewsletterDate(viewModel.latestIssue.publishesOn) }} 發刊
            </p>
          </div>
        </Link>
      </section>

      <p
        v-else
        class="rounded-lg border border-theme-200 bg-white p-6 text-center text-theme-700 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400"
      >
        第一期正在準備中，敬請期待。
      </p>

      <section v-if="issues.length > 0" class="space-y-3">
        <h3 class="text-xl font-bold text-theme-900 dark:text-zinc-100">
          過往期刊
        </h3>
        <ol
          class="grid gap-4 sm:grid-cols-2"
          data-testid="newsletter-issue-list"
        >
          <li v-for="issue in issues" :key="issue.issueKey">
            <Link
              :href="issue.url"
              class="group block h-full overflow-hidden rounded-lg border border-theme-200 bg-white shadow-sm transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900"
            >
              <img
                v-if="issue.coverImageUrl"
                :src="issue.coverImageUrl"
                alt=""
                loading="lazy"
                class="aspect-[3/1] w-full object-cover"
                data-testid="newsletter-cover-image"
              />
              <div
                v-else
                class="flex aspect-[3/1] w-full items-center justify-center bg-theme-100 text-theme-700 dark:bg-zinc-800 dark:text-zinc-600"
                data-testid="newsletter-cover-placeholder"
              >
                <Icon name="book-open" class="size-8" />
              </div>
              <div class="space-y-1 p-4">
                <p
                  class="font-semibold text-theme-900 group-hover:underline dark:text-zinc-100"
                >
                  {{ issue.title }}
                </p>
                <p class="text-sm text-theme-700 dark:text-zinc-400">
                  {{ formatNewsletterDate(issue.publishesOn) }} 發刊
                </p>
              </div>
            </Link>
          </li>
        </ol>
      </section>

      <nav
        v-if="viewModel.issues.last_page > 1"
        aria-label="分頁"
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
