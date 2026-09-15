<script setup>
// Vue port of resources/views/course/show.blade.php. Purely presentational:
// no Alpine components were used on this page, so everything here is plain
// Vue template logic ported from the Blade @php blocks and helpers
// (Str::toSemesterDisplay/toFilenameSafe, the CC by-date exam formatting).
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { StarIcon } from '@heroicons/vue/24/solid'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
  fallbackBackUrl: {
    type: String,
    required: true,
  },
})

const course = computed(() => props.viewModel.course)

// Port of the `Str::toSemesterDisplay()` macro (app/Providers/AppServiceProvider.php).
function toSemesterDisplay(semester) {
  const match = /^(\d{4})([ABC])$/.exec(semester ?? '')

  if (!match) {
    return semester
  }

  const rocYear = Number(match[1]) - 1911
  const termName = { A: '上學期', B: '下學期', C: '暑期' }[match[2]]

  return `${rocYear} 學年度${termName}`
}

// Port of the `Str::toFilenameSafe()` macro.
function toFilenameSafe(value) {
  return value.replace(/[/\\:*?"<>|]/g, '').trim()
}

const examSubjectName = computed(() => toFilenameSafe(course.value.name))

const seoDescription = computed(() => {
  let description = `${course.value.name} 是國立空中大學`

  if (course.value.department) {
    description += ` ${course.value.department}`
  }
  if (course.value.term) {
    description += ` 在 ${toSemesterDisplay(course.value.term)}`
  }
  description += ' 開設的'
  description += course.value.credits
    ? ` ${course.value.credits} 學分課程`
    : '課程'

  return description
})

const pageTitle = computed(() => `${course.value.name} - 檢視課程 - NOU 小幫手`)

const currentUrl = computed(() =>
  typeof window !== 'undefined' ? window.location.href : ''
)

const jsonLd = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'Course',
  name: course.value.name,
  description: seoDescription.value,
  courseCode: String(course.value.id),
  provider: {
    '@type': 'CollegeOrUniversity',
    name: '國立空中大學',
  },
  url: currentUrl.value,
  inLanguage: 'zh-Hant',
}))

const backUrl = computed(() =>
  props.viewModel.previousSchedule
    ? `/schedules/${props.viewModel.previousSchedule.token}`
    : props.fallbackBackUrl
)

// Port of `mb_substr($teacher, -2, null, 'UTF-8')` splitting the trailing
// 老師 suffix off so it can render at a smaller size.
function teacherNameParts(teacherName) {
  const suffix = teacherName.slice(-2)

  if (suffix !== '老師') {
    return { base: teacherName, suffix: null }
  }

  return { base: teacherName.slice(0, -2), suffix }
}

const starCount = computed(() => Math.floor(course.value.credits ?? 0))
const displayStars = computed(() => Math.min(starCount.value, 6))

const CLASS_TYPE_ORDER = [
  'morning',
  'afternoon',
  'evening',
  'full_remote',
  'micro_credit',
  'computer_lab',
]

const classesByType = computed(() => {
  const groups = new Map()

  course.value.classes.forEach(courseClass => {
    if (!groups.has(courseClass.type)) {
      groups.set(courseClass.type, [])
    }

    groups.get(courseClass.type).push(courseClass)
  })

  return CLASS_TYPE_ORDER.filter(type => groups.has(type)).map(type => ({
    type,
    label: groups.get(type)[0].typeLabel,
    classes: groups.get(type),
  }))
})

// Groups a class's session dates and picks the first entry per date, mirroring
// `$class->sessions->toCollection()->sortBy('date')->groupBy(fn ($s) => $s->date)`.
function sessionsByDate(sessions) {
  const map = new Map()

  ;[...sessions]
    .sort((a, b) => a.date.localeCompare(b.date))
    .forEach(session => {
      if (!map.has(session.date)) {
        map.set(session.date, session)
      }
    })

  return [...map.entries()].map(([date, session]) => ({ date, session }))
}

// `Date::parse($d)->isoFormat('M/D (dd)')`, parsed from the date components
// directly so it isn't affected by the viewer's timezone.
function formatSessionDate(dateString) {
  const [year, month, day] = dateString.split('-').map(Number)
  const weekday = ['日', '一', '二', '三', '四', '五', '六'][
    new Date(Date.UTC(year, month - 1, day)).getUTCDay()
  ]

  return `${month}/${day} (${weekday})`
}

function fileExtension(path) {
  const match = /\.([^.\\/]+)$/.exec(path ?? '')

  return match ? match[1] : ''
}

function examReferenceUrl(reference) {
  return `https://noustud.nou.edu.tw/shared_tmp/work/exa/refans/${reference}`
}
</script>

<template>
  <Head :title="pageTitle">
    <meta name="description" :content="seoDescription" />
    <script type="application/ld+json">
      {{ JSON.stringify(jsonLd) }}
    </script>
  </Head>

  <AppLayout>
    <div class="mx-auto max-w-5xl">
      <div class="mb-8">
        <a
          :href="backUrl"
          class="mb-4 inline-flex items-center justify-center gap-2 text-orange-600 hover:text-orange-700"
        >
          <Icon name="chevron-left" class="size-4" />
          回到我的課表
        </a>
        <h2 class="mb-2 text-3xl font-bold text-warm-900 dark:text-zinc-100">
          {{ course.name }}
        </h2>

        <div
          v-if="course.term"
          class="mb-4 text-sm text-warm-600 dark:text-zinc-400"
        >
          {{ toSemesterDisplay(course.term) }}
        </div>
      </div>

      <!-- Course Information -->
      <div
        class="mb-6 rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="mb-4">
          <h2
            class="mb-1 text-xl font-semibold text-warm-900 dark:text-zinc-100"
          >
            課程資訊
          </h2>
        </div>

        <dl class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div v-if="course.descriptionUrl">
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              科目內容
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              <a
                :href="course.descriptionUrl"
                target="_blank"
                rel="noopener"
                data-analytics-event="course_description_open"
                data-analytics-feature="course"
                class="inline-flex items-center gap-2 text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
              >
                檢視詳細內容
                <Icon name="arrow-top-right-on-square" class="size-4" />
              </a>
            </dd>
          </div>

          <div v-if="course.creditType">
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              必/選修
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              {{ course.creditType }}
            </dd>
          </div>

          <div v-if="course.credits">
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              學分
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              <div class="flex items-center gap-3">
                <div
                  class="flex items-center gap-1 text-orange-500"
                  aria-hidden="true"
                >
                  <StarIcon
                    v-for="star in displayStars"
                    :key="star"
                    class="size-4"
                  />
                  <span
                    v-if="starCount > displayStars"
                    class="text-xs text-warm-600 dark:text-zinc-400"
                  >
                    +{{ starCount - displayStars }}
                  </span>
                </div>

                <div class="text-sm text-warm-600 dark:text-zinc-400">
                  {{ course.credits }} 學分
                </div>
              </div>
            </dd>
          </div>

          <div v-if="course.department">
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              學系
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              {{ course.department }}
            </dd>
          </div>

          <div v-if="viewModel.inPersonClassType">
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              面授類別
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              {{ viewModel.inPersonClassType }}
            </dd>
          </div>

          <div v-if="viewModel.media">
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              媒體
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              {{ viewModel.media }}
            </dd>
          </div>

          <div v-if="viewModel.multimediaUrl">
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              多媒體簡介
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              <a
                :href="viewModel.multimediaUrl"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-2 text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
              >
                檢視簡介
                <Icon name="arrow-top-right-on-square" class="size-4" />
              </a>
            </dd>
          </div>

          <div v-if="course.nature">
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              課程性質
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              {{ course.nature }}
            </dd>
          </div>

          <div
            v-if="
              course.midtermDate ||
              course.finalDate ||
              course.examTimeStart ||
              course.examTimeEnd
            "
          >
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              考試資訊
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              <div v-if="course.midtermDate" class="mb-2">
                <div class="font-semibold">期中考</div>
                <div
                  class="flex items-center justify-start gap-x-2 text-sm text-warm-700 tabular-nums dark:text-zinc-300"
                >
                  <div>{{ formatSessionDate(course.midtermDate) }}</div>

                  <div
                    v-if="course.examTimeStart || course.examTimeEnd"
                    class="text-sm whitespace-nowrap text-warm-600 dark:text-zinc-400"
                  >
                    <template v-if="course.examTimeStart && course.examTimeEnd">
                      {{ course.examTimeStart }} - {{ course.examTimeEnd }}
                    </template>
                    <template v-else>
                      {{ course.examTimeStart ?? course.examTimeEnd }}
                    </template>
                  </div>
                </div>
              </div>

              <div v-if="course.finalDate">
                <div class="font-semibold">期末考</div>
                <div
                  class="flex items-center justify-start gap-x-2 text-sm text-warm-700 tabular-nums dark:text-zinc-300"
                >
                  <div>{{ formatSessionDate(course.finalDate) }}</div>

                  <div
                    v-if="course.examTimeStart || course.examTimeEnd"
                    class="text-sm whitespace-nowrap text-warm-600 dark:text-zinc-400"
                  >
                    <template v-if="course.examTimeStart && course.examTimeEnd">
                      {{ course.examTimeStart }} - {{ course.examTimeEnd }}
                    </template>
                    <template v-else>
                      {{ course.examTimeStart ?? course.examTimeEnd }}
                    </template>
                  </div>
                </div>
              </div>
            </dd>
          </div>
        </dl>
      </div>

      <!-- 教科書資訊 -->
      <div
        v-if="course.textbook !== null"
        class="mb-6 rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="mb-4">
          <h2
            class="mb-1 text-xl font-semibold text-warm-900 dark:text-zinc-100"
          >
            教科書資訊
          </h2>
        </div>

        <dl
          class="grid grid-cols-1 gap-6 text-warm-700 md:grid-cols-2 dark:text-zinc-300"
        >
          <div>
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              書名
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              {{ course.textbook.bookTitle }}
            </dd>
          </div>

          <div v-if="course.textbook.edition">
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              版本
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              {{ course.textbook.edition }}
            </dd>
          </div>

          <div
            v-if="
              course.textbook.priceInfo &&
              !Number.isNaN(Number(course.textbook.priceInfo))
            "
          >
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              價格
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              ${{ Number(course.textbook.priceInfo).toLocaleString() }}
            </dd>
          </div>
          <div v-else-if="course.textbook.priceInfo">
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              坊間教科書資訊
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              {{ course.textbook.priceInfo }}
            </dd>
          </div>

          <div v-if="course.textbook.referenceUrl">
            <dt class="mb-2 font-semibold text-warm-900 dark:text-zinc-100">
              參考連結
            </dt>
            <dd class="text-warm-700 dark:text-zinc-300">
              <a
                :href="course.textbook.referenceUrl"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-2 text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
              >
                開啟
                <Icon name="arrow-top-right-on-square" class="size-4" />
              </a>
            </dd>
          </div>
        </dl>
      </div>

      <!-- Course Classes -->
      <div
        v-if="course.classes.length > 0"
        class="mb-6 rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="mb-4">
          <h2
            class="mb-1 text-xl font-semibold text-warm-900 dark:text-zinc-100"
          >
            視訊面授班級與上課時間
          </h2>
        </div>

        <div class="space-y-6">
          <div v-for="group in classesByType" :key="group.type">
            <div class="mb-3 font-semibold text-warm-900 dark:text-zinc-100">
              {{ group.label }}
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
              <div
                v-for="courseClass in group.classes"
                :key="courseClass.id"
                class="rounded-lg border-2 border-warm-200 bg-warm-50 p-4 dark:border-zinc-700 dark:bg-zinc-950"
              >
                <div class="mb-3">
                  <div class="flex items-start justify-between">
                    <div>
                      <div
                        class="font-semibold text-warm-900 dark:text-zinc-100"
                      >
                        {{ courseClass.code }}
                      </div>
                      <div
                        v-if="courseClass.teacherName"
                        class="mt-1 truncate text-sm text-warm-700 dark:text-zinc-300"
                      >
                        <span
                          v-if="
                            teacherNameParts(courseClass.teacherName).suffix
                          "
                          class="inline-flex items-baseline gap-0.5"
                        >
                          <span>{{
                            teacherNameParts(courseClass.teacherName).base
                          }}</span>
                          <span class="text-xs">{{
                            teacherNameParts(courseClass.teacherName).suffix
                          }}</span>
                        </span>
                        <template v-else>{{
                          courseClass.teacherName
                        }}</template>
                      </div>
                    </div>
                    <div
                      class="text-sm whitespace-nowrap text-warm-600 dark:text-zinc-400"
                    >
                      <div v-if="courseClass.startTime">
                        {{ courseClass.startTime }} - {{ courseClass.endTime }}
                      </div>
                    </div>
                  </div>
                </div>

                <div
                  v-if="courseClass.link || courseClass.backupClassroomUrl"
                  class="mb-3 flex flex-wrap gap-2"
                >
                  <a
                    v-if="courseClass.link"
                    :href="courseClass.link"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1 rounded-full border border-orange-200 bg-orange-50 px-3 py-1.5 text-sm font-semibold text-orange-700 transition hover:bg-orange-100 dark:border-orange-800/60 dark:bg-orange-950/60 dark:text-orange-300 dark:hover:bg-orange-950"
                  >
                    <Icon name="video-camera" class="size-4" />
                    視訊上課
                  </a>

                  <a
                    v-if="courseClass.backupClassroomUrl"
                    :href="courseClass.backupClassroomUrl"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1 rounded-full border border-warm-200 bg-white px-3 py-1.5 text-sm font-semibold text-warm-700 transition hover:bg-warm-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-900"
                  >
                    <Icon name="squares-plus" class="size-4" />
                    備用教室
                  </a>
                </div>

                <div
                  v-if="courseClass.sessions.length > 0"
                  class="mt-2 rounded bg-white p-3 dark:bg-zinc-900"
                >
                  <p
                    class="mb-2 text-sm font-semibold text-warm-900 dark:text-zinc-100"
                  >
                    視訊面授日期：
                  </p>

                  <div
                    class="space-y-1 text-sm text-warm-700 dark:text-zinc-300"
                  >
                    <div
                      v-for="entry in sessionsByDate(courseClass.sessions)"
                      :key="entry.date"
                      class="flex items-center justify-between tabular-nums"
                    >
                      <div class="font-semibold">
                        {{ formatSessionDate(entry.date) }}
                      </div>

                      <div
                        v-if="entry.session.startTime || entry.session.endTime"
                        class="text-sm whitespace-nowrap text-warm-600 dark:text-zinc-400"
                      >
                        <template
                          v-if="
                            entry.session.startTime && entry.session.endTime
                          "
                        >
                          {{ entry.session.startTime }} -
                          {{ entry.session.endTime }}
                        </template>
                        <template v-else>
                          {{ entry.session.startTime || entry.session.endTime }}
                        </template>
                      </div>
                    </div>
                  </div>
                </div>
                <p v-else class="mt-2 text-sm text-warm-600 dark:text-zinc-400">
                  未設定上課時間
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Previous Exams Section (only shown when user has a schedule cookie) -->
      <div
        v-if="viewModel.previousSchedule && course.previousExams.length > 0"
        id="previous-exams"
        class="mb-6 rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 print:hidden"
      >
        <div class="mb-4">
          <h2
            class="mb-1 text-xl font-semibold text-warm-900 dark:text-zinc-100"
          >
            考古題
          </h2>
        </div>

        <!-- 手機：卡片列表 -->
        <div class="space-y-3 md:hidden">
          <div
            v-for="(exam, index) in course.previousExams"
            :key="index"
            class="rounded-lg border border-warm-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <div class="mb-3 font-semibold text-warm-900 dark:text-zinc-100">
              {{ exam.term ?? '-' }}
            </div>

            <div class="grid grid-cols-2 gap-3 text-sm">
              <div>
                <p class="mb-1 font-semibold text-warm-600 dark:text-zinc-400">
                  期中考正參
                </p>
                <a
                  v-if="exam.midtermReferencePrimary"
                  :href="examReferenceUrl(exam.midtermReferencePrimary)"
                  target="_blank"
                  rel="noopener"
                  :aria-label="`${exam.term}的期中考正參`"
                  :download="`${examSubjectName}_${exam.term}_期中考正參.${fileExtension(exam.midtermReferencePrimary)}`"
                  class="inline-flex items-center gap-2 text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
                >
                  正參
                  <Icon name="arrow-top-right-on-square" class="size-4" />
                </a>
                <span v-else class="text-warm-500 dark:text-zinc-400">—</span>
              </div>

              <div>
                <p class="mb-1 font-semibold text-warm-600 dark:text-zinc-400">
                  期中考副參
                </p>
                <a
                  v-if="exam.midtermReferenceSecondary"
                  :href="examReferenceUrl(exam.midtermReferenceSecondary)"
                  target="_blank"
                  rel="noopener"
                  :aria-label="`${exam.term}的期中考副參`"
                  :download="`${examSubjectName}_${exam.term}_期中考副參.${fileExtension(exam.midtermReferenceSecondary)}`"
                  class="inline-flex items-center gap-2 text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
                >
                  副參
                  <Icon name="arrow-top-right-on-square" class="size-4" />
                </a>
                <span v-else class="text-warm-500 dark:text-zinc-400">—</span>
              </div>

              <div>
                <p class="mb-1 font-semibold text-warm-600 dark:text-zinc-400">
                  期末考正參
                </p>
                <a
                  v-if="exam.finalReferencePrimary"
                  :href="examReferenceUrl(exam.finalReferencePrimary)"
                  target="_blank"
                  rel="noopener"
                  :aria-label="`${exam.term}的期末考正參`"
                  :download="`${examSubjectName}_${exam.term}_期末考正參.${fileExtension(exam.finalReferencePrimary)}`"
                  class="inline-flex items-center gap-2 text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
                >
                  正參
                  <Icon name="arrow-top-right-on-square" class="size-4" />
                </a>
                <span v-else class="text-warm-500 dark:text-zinc-400">—</span>
              </div>

              <div>
                <p class="mb-1 font-semibold text-warm-600 dark:text-zinc-400">
                  期末考副參
                </p>
                <a
                  v-if="exam.finalReferenceSecondary"
                  :href="examReferenceUrl(exam.finalReferenceSecondary)"
                  target="_blank"
                  rel="noopener"
                  :aria-label="`${exam.term}的期末考副參`"
                  :download="`${examSubjectName}_${exam.term}_期末考副參.${fileExtension(exam.finalReferenceSecondary)}`"
                  class="inline-flex items-center gap-2 text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
                >
                  副參
                  <Icon name="arrow-top-right-on-square" class="size-4" />
                </a>
                <span v-else class="text-warm-500 dark:text-zinc-400">—</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 桌面：維持表格，但只在 md+ 顯示 -->
        <div class="hidden overflow-x-auto md:block">
          <table
            class="w-full border-collapse overflow-hidden rounded text-left"
          >
            <caption class="sr-only">
              考古題
            </caption>
            <thead
              class="border-b-2 border-warm-300 bg-warm-100 dark:border-zinc-600 dark:bg-zinc-900"
            >
              <tr>
                <th
                  scope="col"
                  class="px-4 py-3 text-center font-bold text-warm-900 dark:text-zinc-100"
                >
                  學期
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 text-center font-bold text-warm-900 dark:text-zinc-100"
                >
                  期中考正參
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 text-center font-bold text-warm-900 dark:text-zinc-100"
                >
                  期中考副參
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 text-center font-bold text-warm-900 dark:text-zinc-100"
                >
                  期末考正參
                </th>
                <th
                  scope="col"
                  class="px-4 py-3 text-center font-bold text-warm-900 dark:text-zinc-100"
                >
                  期末考副參
                </th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(exam, index) in course.previousExams"
                :key="index"
                class="border-b border-warm-200 dark:border-zinc-700"
              >
                <th
                  scope="row"
                  class="px-4 py-3 text-center font-normal text-warm-800 tabular-nums dark:text-zinc-200"
                >
                  {{ exam.term ?? '-' }}
                </th>
                <td
                  class="px-4 py-3 text-center text-warm-800 dark:text-zinc-200"
                >
                  <a
                    v-if="exam.midtermReferencePrimary"
                    :href="examReferenceUrl(exam.midtermReferencePrimary)"
                    target="_blank"
                    rel="noopener"
                    :aria-label="`${exam.term}的期中考正參`"
                    :download="`${examSubjectName}_${exam.term}_期中考正參.${fileExtension(exam.midtermReferencePrimary)}`"
                    class="inline-flex items-center gap-2 text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
                  >
                    正參
                    <Icon name="arrow-top-right-on-square" class="size-4" />
                  </a>
                  <template v-else>—</template>
                </td>
                <td
                  class="px-4 py-3 text-center text-warm-800 dark:text-zinc-200"
                >
                  <a
                    v-if="exam.midtermReferenceSecondary"
                    :href="examReferenceUrl(exam.midtermReferenceSecondary)"
                    target="_blank"
                    rel="noopener"
                    :aria-label="`${exam.term}的期中考副參`"
                    :download="`${examSubjectName}_${exam.term}_期中考副參.${fileExtension(exam.midtermReferenceSecondary)}`"
                    class="inline-flex items-center gap-2 text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
                  >
                    副參
                    <Icon name="arrow-top-right-on-square" class="size-4" />
                  </a>
                  <template v-else>—</template>
                </td>
                <td
                  class="px-4 py-3 text-center text-warm-800 dark:text-zinc-200"
                >
                  <a
                    v-if="exam.finalReferencePrimary"
                    :href="examReferenceUrl(exam.finalReferencePrimary)"
                    target="_blank"
                    rel="noopener"
                    :aria-label="`${exam.term}的期末考正參`"
                    :download="`${examSubjectName}_${exam.term}_期末考正參.${fileExtension(exam.finalReferencePrimary)}`"
                    class="inline-flex items-center gap-2 text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
                  >
                    正參
                    <Icon name="arrow-top-right-on-square" class="size-4" />
                  </a>
                  <template v-else>—</template>
                </td>
                <td
                  class="px-4 py-3 text-center text-warm-800 dark:text-zinc-200"
                >
                  <a
                    v-if="exam.finalReferenceSecondary"
                    :href="examReferenceUrl(exam.finalReferenceSecondary)"
                    target="_blank"
                    rel="noopener"
                    :aria-label="`${exam.term}的期末考副參`"
                    :download="`${examSubjectName}_${exam.term}_期末考副參.${fileExtension(exam.finalReferenceSecondary)}`"
                    class="inline-flex items-center gap-2 text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
                  >
                    副參
                    <Icon name="arrow-top-right-on-square" class="size-4" />
                  </a>
                  <template v-else>—</template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 常用連結 -->
      <div
        class="mb-6 w-full rounded-lg border border-warm-200 bg-white p-6 md:w-auto dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="mb-4">
          <h2
            class="mb-1 text-xl font-semibold text-warm-900 dark:text-zinc-100"
          >
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
            data-offline-allow
            class="flex items-center justify-center gap-2 truncate rounded border border-warm-200 bg-white px-3 py-2 text-base text-warm-700 hover:bg-warm-50 md:flex-col dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-950"
          >
            <Icon name="academic-cap" class="size-8 md:size-16" />
            <span class="flex grow">學校官網</span>
          </a>

          <a
            href="https://noustud.nou.edu.tw/"
            target="_blank"
            rel="noopener noreferrer"
            data-offline-allow
            class="flex items-center justify-center gap-2 truncate rounded border border-warm-200 bg-white px-3 py-2 text-base text-warm-700 hover:bg-warm-50 md:flex-col dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-950"
          >
            <Icon name="computer-desktop" class="size-8 md:size-16" />
            <span class="flex grow">教務行政資訊系統</span>
          </a>

          <a
            href="https://uu.nou.edu.tw/"
            target="_blank"
            rel="noopener noreferrer"
            data-offline-allow
            class="flex items-center justify-center gap-2 truncate rounded border border-warm-200 bg-white px-3 py-2 text-base text-warm-700 hover:bg-warm-50 md:flex-col dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-950"
          >
            <Icon name="globe-alt" class="size-8 md:size-16" />
            <span class="flex grow">數位學習平台 (UU平台)</span>
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
      </div>

      <div
        class="rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="mb-4">
          <h2
            class="mb-1 text-xl font-semibold text-warm-900 dark:text-zinc-100"
          >
            免責聲明
          </h2>
        </div>
        <p class="text-sm text-warm-600 dark:text-zinc-400">
          課程資料來自國立空中大學之公開資料，基於合理使用原則，以非商用、公開的方式供其他上課同學參考使用，資料版權屬於國立空中大學所有。本站只搜集課程之詮釋資料（Metadata），例如課程名稱、教師、學分數、上課時間等，不保存其他資料。
        </p>
      </div>
    </div>
  </AppLayout>
</template>
