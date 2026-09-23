<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const posts = computed(() => props.viewModel.posts.data ?? [])

function formatDate(value) {
  if (!value) {
    return ''
  }

  return new Date(value).toLocaleDateString('zh-TW', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
</script>

<template>
  <Head :title="`${viewModel.title} - NOU 小幫手`" />

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-6">
      <header class="space-y-3">
        <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          {{ viewModel.title }}
        </h2>
        <p class="text-theme-700 dark:text-zinc-300">NOU 小幫手的更新紀錄。</p>
      </header>

      <section v-if="viewModel.latestPost" class="space-y-3">
        <h3 class="text-xl font-bold text-theme-900 dark:text-zinc-100">
          最新更新
        </h3>
        <Link
          :href="viewModel.latestPost.url"
          class="group block rounded-lg border border-theme-200 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900"
          data-testid="changelog-highlight"
        >
          <p
            class="text-xl font-semibold text-theme-900 group-hover:underline dark:text-zinc-100"
          >
            {{ viewModel.latestPost.title }}
          </p>
          <p class="text-sm text-theme-700 dark:text-zinc-400">
            {{ formatDate(viewModel.latestPost.publishedAt) }}
          </p>
        </Link>
      </section>

      <p
        v-else
        class="rounded-lg border border-theme-200 bg-white p-6 text-center text-theme-700 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400"
      >
        還沒有任何更新紀錄。
      </p>

      <section v-if="posts.length > 0" class="space-y-3">
        <h3 class="text-xl font-bold text-theme-900 dark:text-zinc-100">
          過往更新
        </h3>
        <ol class="space-y-3" data-testid="changelog-post-list">
          <li v-for="post in posts" :key="post.slug">
            <Link
              :href="post.url"
              class="group block rounded-lg border border-theme-200 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900"
            >
              <p
                class="font-semibold text-theme-900 group-hover:underline dark:text-zinc-100"
              >
                {{ post.title }}
              </p>
              <p class="text-sm text-theme-700 dark:text-zinc-400">
                {{ formatDate(post.publishedAt) }}
              </p>
            </Link>
          </li>
        </ol>
      </section>

      <nav
        v-if="viewModel.posts.last_page > 1"
        class="flex justify-between text-sm"
      >
        <Link
          v-if="viewModel.posts.prev_page_url"
          :href="viewModel.posts.prev_page_url"
          class="text-theme-700 underline hover:no-underline dark:text-zinc-300"
        >
          較新的更新
        </Link>
        <span v-else></span>
        <Link
          v-if="viewModel.posts.next_page_url"
          :href="viewModel.posts.next_page_url"
          class="text-theme-700 underline hover:no-underline dark:text-zinc-300"
        >
          較舊的更新
        </Link>
      </nav>
    </div>
  </AppLayout>
</template>
