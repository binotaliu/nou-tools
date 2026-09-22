<script setup>
// Purely presentational — all filtering/preference resolution happens
// server-side (see ScheduleController::show's `announcementsWidget` prop).
import { Link } from '@inertiajs/vue3'

defineProps({
  scheduleUuid: {
    type: String,
    required: true,
  },
  hasAnySelection: {
    type: Boolean,
    required: true,
  },
  announcements: {
    type: Array,
    required: true,
  },
  moreAnnouncementsUrl: {
    type: String,
    required: true,
  },
})

function relativeLabel(announcement) {
  return announcement.publishedRelativeLabel
}
</script>

<template>
  <div
    class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
  >
    <div class="mb-4">
      <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
        最新公告
      </h2>
    </div>

    <div class="space-y-1">
      <div
        v-if="!hasAnySelection"
        class="rounded-lg border border-dashed border-theme-300 bg-theme-50 px-4 py-6 text-center text-sm text-theme-600 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-400"
      >
        尚未選擇任何公告分類。
        <Link
          :href="`/schedules/${scheduleUuid}/customize`"
          class="font-medium text-orange-700 hover:underline dark:text-orange-400"
        >
          立即選擇
        </Link>
      </div>

      <div
        v-else-if="announcements.length === 0"
        class="rounded-lg border border-dashed border-theme-300 bg-theme-50 px-4 py-6 text-center text-sm text-theme-600 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-400"
      >
        目前沒有符合條件的最新公告。
      </div>

      <template v-else>
        <div
          v-for="(announcement, index) in announcements"
          :key="index"
          class="flex flex-col gap-1 border-b border-theme-100 py-2 last:border-0 dark:border-zinc-800"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
              <span
                class="rounded-full bg-theme-100 px-2 py-0.5 font-medium text-theme-800 dark:bg-zinc-800 dark:text-zinc-200"
              >
                {{ announcement.sourceName }}
              </span>
              <span
                class="rounded-full bg-orange-100 px-2 py-0.5 font-medium text-orange-700 dark:bg-orange-950/60 dark:text-orange-300"
              >
                {{ announcement.category }}
              </span>
            </div>

            <div class="flex-1"></div>

            <p
              class="shrink-0 text-right text-xs whitespace-nowrap text-theme-500 dark:text-zinc-400"
            >
              <template v-if="announcement.publishedAt">
                {{ relativeLabel(announcement) }} •
                {{ announcement.publishedDateDisplay }}
              </template>
              <template v-else>未提供</template>
            </p>
          </div>

          <a
            :href="announcement.url"
            target="_blank"
            rel="noopener noreferrer"
            class="line-clamp-1! block max-w-full text-sm font-medium break-all text-theme-900 transition hover:text-orange-700 dark:text-zinc-100 dark:hover:text-orange-400"
          >
            {{ announcement.title }}
          </a>
        </div>
      </template>

      <div
        class="flex flex-col gap-2 pt-2 sm:flex-row sm:items-center sm:justify-between"
      >
        <Link
          :href="moreAnnouncementsUrl"
          class="text-sm font-medium text-orange-700 hover:underline dark:text-orange-400"
        >
          檢視更多公告
        </Link>

        <Link
          :href="`/schedules/${scheduleUuid}/customize`"
          class="text-sm text-theme-600 hover:underline dark:text-zinc-400"
        >
          選擇公告分類
        </Link>
      </div>
    </div>
  </div>
</template>
