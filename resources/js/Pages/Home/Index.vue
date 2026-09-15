<script setup>
// Vue port of resources/views/home.blade.php (the site's homepage). Reuses
// the already-ported shared widgets (Greeting, SchoolCalendar, CommonLinks)
// rather than the Blade `<x-greeting>`/`<x-school-calendar>`/`<x-common-links>`
// components, which this migration was the last remaining user of. The
// video-course date picker is the `nouDatePicker` Alpine.data() component
// (resources/js/alpine-components.js); useDatePicker.js is its Vue port.
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import Greeting from '../../Components/Greeting.vue'
import CommonLinks from '../../Components/CommonLinks.vue'
import SchoolCalendar from '../../Components/SchoolCalendar.vue'
import useDatePicker from '../../Composables/useDatePicker'

const props = defineProps({
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

const jsonLd = {
  '@context': 'https://schema.org',
  '@type': 'WebSite',
  name: 'NOU 小幫手',
  description:
    '給 NOU 同學的非官方小工具：管理個人課表與學習進度。NOU 小幫手是一款由同學自行開發，專為國立空中大學同學設計的非官方小工具。通過 NOU 小幫手，同學可輕鬆管理自己的課表、學習進度，掌握視訊面授及考試時間，並隨時取得最新的學校公告。另外，NOU 小幫手也提供了優惠店家清單，讓同學在校園生活中享受更多便利與優惠。NOU 小幫手致力於為同學提供一個簡單、方便、實用的學習工具，讓同學能夠更好地規劃自己的學習生活。',
  url: window.location.origin + '/',
}

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

const { date, navigate } = useDatePicker({ date: props.viewModel.selectedDate })

const typeLabels = {
  morning: '上午班',
  afternoon: '下午班',
  evening: '夜間班',
  full_remote: '全遠距',
  micro_credit: '微學分',
  other: '其他',
}

// Port of the Blade view's per-course $grouped/$timeGroups @php blocks:
// group each course's classes by type, then group each type's classes by
// their (possibly session-overridden) start/end time.
const courses = computed(() =>
  props.viewModel.courses.map(course => {
    const grouped = new Map(Object.keys(typeLabels).map(key => [key, []]))

    course.classes.forEach(courseClass => {
      const key = Object.hasOwn(typeLabels, courseClass.type)
        ? courseClass.type
        : 'other'
      grouped.get(key).push(courseClass)
    })

    const groups = Object.entries(typeLabels)
      .filter(([key]) => grouped.get(key).length > 0)
      .map(([key, label]) => {
        const timeGroups = new Map()

        grouped.get(key).forEach(courseClass => {
          const todaySession = courseClass.sessions[0]
          const timeLabel =
            todaySession && todaySession.startTime && todaySession.endTime
              ? `${todaySession.startTime} - ${todaySession.endTime}`
              : courseClass.startTime
                ? `${courseClass.startTime} - ${courseClass.endTime}`
                : '時間未定'

          if (!timeGroups.has(timeLabel)) {
            timeGroups.set(timeLabel, [])
          }

          timeGroups.get(timeLabel).push(courseClass)
        })

        return {
          key,
          label,
          timeGroups: [...timeGroups.entries()].map(
            ([timeLabel, classesAtTime]) => ({ timeLabel, classesAtTime })
          ),
        }
      })

    return { ...course, groups }
  })
)
</script>

<template>
  <Head title="NOU 小幫手">
    <script type="application/ld+json">
      {{ JSON.stringify(jsonLd) }}
    </script>
  </Head>

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
          class="w-full rounded-lg border border-warm-200 bg-white p-6 md:w-auto dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div class="mb-4">
            <h2 class="text-xl font-semibold text-warm-900 dark:text-zinc-100">
              功能選單
            </h2>
          </div>

          <Link
            v-if="viewModel.previousSchedule"
            :href="`/schedules/${viewModel.previousSchedule.token}`"
            data-analytics-event="schedule_open_previous"
            data-analytics-feature="schedule"
            class="flex w-full items-center justify-center gap-2 rounded-lg border border-warm-700 bg-warm-700 px-4 py-2 font-semibold text-white transition hover:bg-warm-800"
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
            class="flex w-full items-center justify-center gap-2 rounded-lg border border-warm-700 bg-warm-700 px-4 py-2 font-semibold text-white transition hover:bg-warm-800"
          >
            <Icon name="table-cells" class="size-4" />

            建立我的課表
          </Link>

          <Link
            href="/announcements"
            class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-warm-500 bg-white px-4 py-2 font-semibold text-warm-900 transition hover:bg-warm-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
          >
            <Icon name="megaphone" class="size-4" />

            學校公告
          </Link>

          <Link
            href="/directory"
            data-offline-allow
            class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-warm-500 bg-white px-4 py-2 font-semibold text-warm-900 transition hover:bg-warm-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
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
              class="text-warm-600 underline hover:text-warm-800 dark:text-zinc-400 dark:hover:text-zinc-200"
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

      <!-- 今日面授 -->
      <div
        class="rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="mb-4">
          <h2 class="text-xl font-semibold text-warm-900 dark:text-zinc-100">
            今日視訊面授
          </h2>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <label
              for="video-course-date"
              class="text-sm text-warm-500 dark:text-zinc-400"
            >
              選擇日期
            </label>
            <input
              id="video-course-date"
              v-model="date"
              type="date"
              class="rounded border px-3 py-1 text-sm"
              data-offline-disable
              @change="navigate()"
            />
          </div>
        </div>

        <div class="mt-4 space-y-6">
          <div
            v-if="courses.length === 0"
            class="flex min-h-64 items-center justify-center gap-x-2 text-2xl text-warm-500 dark:text-zinc-400"
          >
            <Icon name="face-smile" class="size-8" />
            今日無面授課程
          </div>

          <div v-for="course in courses" :key="course.id">
            <h4 class="mb-3 font-semibold text-warm-800 dark:text-zinc-200">
              {{ course.name }}
            </h4>
            <div
              class="ml-2 grid grid-cols-1 gap-2 space-y-2 md:grid-cols-2 lg:grid-cols-3"
            >
              <div
                v-for="group in course.groups"
                :key="group.key"
                class="flex flex-col items-stretch gap-2"
              >
                <div
                  class="text-sm font-semibold text-warm-700 dark:text-zinc-300"
                >
                  {{ group.label }}
                </div>

                <div class="flex w-full flex-col gap-1">
                  <div
                    v-for="timeGroup in group.timeGroups"
                    :key="timeGroup.timeLabel"
                    class="w-full rounded border border-warm-800 bg-white p-3 dark:border-zinc-600 dark:bg-zinc-900"
                  >
                    <div
                      class="mb-3 text-sm font-medium text-warm-600 dark:text-zinc-400"
                    >
                      {{ timeGroup.timeLabel }}
                    </div>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                      <div
                        v-for="courseClass in timeGroup.classesAtTime"
                        :key="courseClass.id"
                        class="flex w-full flex-col gap-2"
                      >
                        <a
                          v-if="courseClass.link"
                          :href="courseClass.link"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="block w-full rounded border border-orange-200 bg-orange-50 px-4 py-3 text-left text-orange-700 transition hover:bg-orange-100 dark:border-orange-800/60 dark:bg-orange-950/60 dark:text-orange-300 dark:hover:bg-orange-950"
                        >
                          <div class="text-lg font-semibold">
                            {{ courseClass.code }}
                          </div>
                          <div
                            v-if="courseClass.teacherName"
                            class="mt-1 truncate text-sm text-warm-600 dark:text-zinc-400"
                          >
                            {{ courseClass.teacherName }}
                          </div>
                        </a>
                        <div
                          v-else
                          class="block w-full rounded border bg-gray-50 px-4 py-3 text-left text-warm-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400"
                        >
                          <div class="text-lg font-semibold">
                            {{ courseClass.code }}
                          </div>
                          <div
                            v-if="courseClass.teacherName"
                            class="mt-1 truncate text-sm text-warm-600 dark:text-zinc-400"
                          >
                            {{ courseClass.teacherName }}
                          </div>
                        </div>

                        <a
                          v-if="courseClass.backupClassroomUrl"
                          :href="courseClass.backupClassroomUrl"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="inline-flex items-center justify-center gap-1 rounded border border-warm-200 bg-warm-50 px-3 py-2 text-sm font-semibold text-warm-700 transition hover:bg-warm-100 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-900"
                        >
                          <Icon name="squares-plus" class="size-4" />
                          備用教室
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
