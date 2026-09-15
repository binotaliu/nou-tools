<script setup>
// Vue port of resources/views/components/common-links.blade.php. The three
// external links and the directory link are static; `customLinks` is the
// per-schedule custom link list (see ScheduleCustomizationPageData).
import Icon from './Icon.vue'

defineProps({
  customLinks: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <div
    class="w-full rounded-lg border border-warm-200 bg-white p-6 md:w-auto dark:border-zinc-700 dark:bg-zinc-900"
  >
    <div class="mb-4">
      <h2 class="text-xl font-semibold text-warm-900 dark:text-zinc-100">
        常用連結
      </h2>
    </div>

    <div
      class="grid grid-cols-1 gap-2 md:grid-cols-3 md:flex-row md:items-center"
    >
      <a
        href="https://www.nou.edu.tw"
        target="_blank"
        rel="noopener noreferrer"
        class="flex inline-flex items-center justify-center gap-2 truncate rounded border border-warm-200 bg-white px-3 py-2 text-base text-warm-700 hover:bg-warm-50 md:flex-col dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-950"
      >
        <Icon name="academic-cap" class="size-8 md:size-16" />
        學校官網
      </a>

      <a
        href="https://noustud.nou.edu.tw/"
        target="_blank"
        rel="noopener noreferrer"
        class="flex inline-flex items-center justify-center gap-2 truncate rounded border border-warm-200 bg-white px-3 py-2 text-base text-warm-700 hover:bg-warm-50 md:flex-col dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-950"
      >
        <Icon name="computer-desktop" class="size-8 md:size-16" />
        教務行政資訊系統
      </a>

      <a
        href="https://uu.nou.edu.tw/"
        target="_blank"
        rel="noopener noreferrer"
        class="flex inline-flex items-center justify-center gap-2 truncate rounded border border-warm-200 bg-white px-3 py-2 text-base text-warm-700 hover:bg-warm-50 md:flex-col dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-950"
      >
        <Icon name="globe-alt" class="size-8 md:size-16" />
        數位學習平台 (UU平台)
      </a>
    </div>

    <div class="mt-2 flex justify-end">
      <a
        href="/directory"
        data-offline-allow
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-warm-700 bg-warm-700 px-3 py-1 text-sm font-semibold text-white transition hover:bg-warm-800"
      >
        連結 / 學習指導中心目錄
      </a>
    </div>

    <div
      v-if="customLinks.length > 0"
      class="mt-4 flex flex-wrap items-start gap-x-4 gap-y-2 rounded border-2 border-dotted border-warm-700/50 px-4 py-2"
    >
      <a
        v-for="link in customLinks"
        :key="link.url"
        :href="link.url"
        class="mb-0 inline-flex items-center justify-center gap-2 rounded-lg border border-warm-500 bg-white px-4 py-2 font-semibold text-warm-900 transition hover:bg-warm-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
      >
        <Icon name="link" class="size-4" />
        {{ link.title }}
      </a>
    </div>
  </div>
</template>
