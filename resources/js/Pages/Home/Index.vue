<script setup>
// The site's homepage. Composed from the shared Greeting, SchoolCalendar,
// CommonLinks and VideoCourses (今日視訊面授) components.
import { onMounted, onUnmounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import HeroCarousel from '../../Components/Home/HeroCarousel.vue'
import VideoCourses from '../../Components/Home/VideoCourses.vue'
import Greeting from '../../Components/Greeting.vue'
import CommonLinks from '../../Components/CommonLinks.vue'
import SchoolCalendar from '../../Components/SchoolCalendar.vue'

defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
  greeting: {
    type: Object,
    required: true,
  },
  schoolCalendar: {
    type: Object,
    required: true,
  },
})

// --- offline banner (same one-off pattern as Directory/Index.vue and
// Schedule/Show.vue) ---
const offline = ref(typeof navigator !== 'undefined' && !navigator.onLine)

function handleOnline() {
  offline.value = false
}

function handleOffline() {
  offline.value = true
}

onMounted(() => {
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
})

onUnmounted(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
})
</script>

<template>
  <Head title="NOU 小幫手" />

  <AppLayout>
    <div class="space-y-8">
      <div
        v-show="offline"
        class="mb-6 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200 print:hidden"
        role="status"
        aria-live="polite"
      >
        <Icon name="signal-slash" class="mt-0.5 size-5 shrink-0" />
        <div>
          <p class="font-semibold">目前處於離線狀態</p>
          <p class="mt-1">
            這是先前載入過的快取內容，可能不是最新資料。部分需要連線的功能已停用，例如今日視訊面授的日期切換。
          </p>
        </div>
      </div>

      <HeroCarousel v-if="!viewModel.previousSchedule" />

      <Greeting
        :semester-label="greeting.semesterLabel"
        :semester-code="greeting.semesterCode"
        :semester-start="greeting.semesterStart"
        :semester-end="greeting.semesterEnd"
      />

      <div
        class="flex flex-col gap-4 md:flex-row md:items-stretch md:justify-between"
      >
        <div
          class="w-full rounded-lg border border-theme-200 bg-white p-6 md:w-auto dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div class="mb-4">
            <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
              功能選單
            </h2>
          </div>

          <Link
            v-if="viewModel.previousSchedule"
            :href="`/schedules/${viewModel.previousSchedule.token}`"
            data-analytics-event="schedule_open_previous"
            data-analytics-feature="schedule"
            class="flex w-full items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-4 py-2 font-semibold text-white transition hover:bg-theme-800"
          >
            <Icon name="table-cells" class="size-4" />

            <span class="max-w-xs truncate">
              {{ viewModel.previousSchedule.name ?? '（未命名）' }}
            </span>
          </Link>
          <Link
            v-else
            href="/schedules/create"
            data-analytics-event="schedule_create_start"
            data-analytics-feature="schedule"
            class="flex w-full items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-4 py-2 font-semibold text-white transition hover:bg-theme-800"
          >
            <Icon name="table-cells" class="size-4" />

            建立我的課表
          </Link>

          <Link
            href="/announcements"
            class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
          >
            <Icon name="megaphone" class="size-4" />

            學校公告
          </Link>

          <Link
            href="/directory"
            data-offline-allow
            class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
          >
            <Icon name="map" class="size-4" />

            連結 / 學習指導中心目錄
          </Link>

          <div
            v-if="viewModel.previousSchedule"
            class="mt-3 w-full text-center text-sm"
          >
            <Link
              href="/schedules/create"
              class="text-theme-600 underline hover:text-theme-800 dark:text-zinc-400 dark:hover:text-zinc-200"
              data-analytics-event="schedule_create_start"
              data-analytics-feature="schedule"
            >
              建立新課表
            </Link>
          </div>
        </div>

        <CommonLinks />
      </div>

      <!-- School Calendar -->
      <SchoolCalendar
        :events="schoolCalendar.events"
        :show-past-events="schoolCalendar.showPastEvents"
      />

      <VideoCourses
        :courses="viewModel.courses"
        :selected-date="viewModel.selectedDate"
        :today="viewModel.today"
      />
    </div>
  </AppLayout>
</template>
