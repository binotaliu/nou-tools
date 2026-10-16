<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'

defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <Head :title="`分類：${viewModel.name} - NOU 小幫手`" />

  <AppLayout>
    <div class="mx-auto max-w-4xl space-y-6">
      <nav aria-label="麵包屑" class="text-sm">
        <Link
          href="/wiki"
          class="inline-flex items-center gap-1 text-theme-700 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100"
        >
          <Icon name="chevron-left" class="size-3" />
          知識庫
        </Link>
      </nav>

      <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
        分類：{{ viewModel.name }}
      </h2>

      <ul
        class="space-y-3 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
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
    </div>
  </AppLayout>
</template>
