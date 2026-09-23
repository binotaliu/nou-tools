<script setup>
// Homepage 今日視訊面授: the courses with a video in-person class on the
// selected day. Each course is one row group; inside it every period (上午班,
// 夜間班, …) gets a row with its time on the left and the class chips on the
// right, so there are no boxes nested in boxes. The date filter is driven by
// useDatePicker.js and navigates with ?date=.
import { computed } from 'vue'
import Icon from '../Icon.vue'
import DateField from '../DateField.vue'
import useDatePicker from '../../Composables/useDatePicker'

const props = defineProps({
  courses: {
    type: Array,
    required: true,
  },
  selectedDate: {
    type: String,
    required: true,
  },
  // The server's date, so the calendar's "today" follows Taipei time.
  today: {
    type: String,
    required: true,
  },
})

const { date, navigate } = useDatePicker({ date: props.selectedDate })

function selectDate(next) {
  date.value = next
  navigate()
}

const typeLabels = {
  morning: '上午班',
  afternoon: '下午班',
  evening: '夜間班',
  full_remote: '全遠距',
  micro_credit: '微學分',
  other: '其他',
}

// Per course: one slot per (class type, start/end time). A class's time is the
// selected day's session when it overrides the class's default.
const groupedCourses = computed(() =>
  props.courses.map(course => {
    const byType = new Map(Object.keys(typeLabels).map(key => [key, []]))

    course.classes.forEach(courseClass => {
      const key = Object.hasOwn(typeLabels, courseClass.type)
        ? courseClass.type
        : 'other'
      byType.get(key).push(courseClass)
    })

    const slots = []

    Object.entries(typeLabels).forEach(([key, label]) => {
      const byTime = new Map()

      byType.get(key).forEach(courseClass => {
        const todaySession = courseClass.sessions[0]
        const timeLabel =
          todaySession && todaySession.startTime && todaySession.endTime
            ? `${todaySession.startTime} - ${todaySession.endTime}`
            : courseClass.startTime
              ? `${courseClass.startTime} - ${courseClass.endTime}`
              : '時間未定'

        if (!byTime.has(timeLabel)) {
          byTime.set(timeLabel, [])
        }

        byTime.get(timeLabel).push(courseClass)
      })

      byTime.forEach((classes, timeLabel) => {
        slots.push({ key: `${key}-${timeLabel}`, label, timeLabel, classes })
      })
    })

    return {
      ...course,
      slots,
      classCount: course.classes.length,
    }
  })
)

const classTotal = computed(() =>
  groupedCourses.value.reduce((sum, course) => sum + course.classCount, 0)
)
</script>

<template>
  <section
    class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
  >
    <div
      class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
      <div>
        <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
          今日視訊面授
        </h2>
        <p
          v-if="groupedCourses.length > 0"
          class="mt-1 text-sm text-theme-700 tabular-nums dark:text-zinc-400"
        >
          共 {{ groupedCourses.length }} 門課程、{{ classTotal }} 個班級
        </p>
      </div>

      <div class="flex items-center gap-2">
        <label
          for="video-course-date"
          class="text-sm text-theme-700 dark:text-zinc-400"
        >
          選擇日期
        </label>
        <DateField
          id="video-course-date"
          :model-value="date"
          label="選擇日期"
          variant="field"
          :clearable="false"
          :today="today"
          data-offline-disable
          @change="selectDate"
        />
      </div>
    </div>

    <div
      v-if="groupedCourses.length === 0"
      class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-theme-300 bg-theme-50 px-4 py-12 text-theme-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-400"
    >
      <Icon name="face-smile" class="size-8" />
      <p class="text-lg font-medium">今日無面授課程</p>
    </div>

    <div v-else class="divide-y divide-theme-100 dark:divide-zinc-800">
      <div
        v-for="course in groupedCourses"
        :key="course.id"
        class="py-4 first:pt-0 last:pb-0"
      >
        <h3 class="font-semibold text-theme-900 dark:text-zinc-100">
          {{ course.name }}
        </h3>

        <div class="mt-3 space-y-3">
          <div
            v-for="slot in course.slots"
            :key="slot.key"
            class="flex flex-col gap-2 sm:flex-row sm:items-start sm:gap-4"
          >
            <div
              class="flex shrink-0 items-center gap-2 sm:w-36 sm:flex-col sm:items-start sm:gap-1 sm:pt-1.5"
            >
              <span
                class="rounded-full bg-theme-100 px-2 py-0.5 text-xs font-medium text-theme-800 dark:bg-zinc-800 dark:text-zinc-200"
              >
                {{ slot.label }}
              </span>
              <span
                class="inline-flex items-center gap-1 text-sm text-theme-700 tabular-nums dark:text-zinc-400"
              >
                <Icon name="clock" class="size-3.5" />
                {{ slot.timeLabel }}
              </span>
            </div>

            <div class="flex min-w-0 flex-1 flex-wrap gap-2">
              <div
                v-for="courseClass in slot.classes"
                :key="courseClass.id"
                class="flex w-full items-stretch overflow-hidden rounded-lg border bg-theme-50 sm:w-auto sm:min-w-44 dark:bg-zinc-950"
                :class="
                  courseClass.link
                    ? 'border-theme-200 dark:border-zinc-700'
                    : 'border-dashed border-theme-300 dark:border-zinc-600'
                "
              >
                <a
                  v-if="courseClass.link"
                  :href="courseClass.link"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="group flex min-w-0 flex-1 items-center gap-3 px-3 py-2 transition hover:bg-theme-100 dark:hover:bg-zinc-800"
                >
                  <div class="min-w-0 flex-1">
                    <div
                      class="font-semibold text-theme-800 tabular-nums dark:text-zinc-100"
                    >
                      {{ courseClass.code }}
                    </div>
                    <div
                      v-if="courseClass.teacherName"
                      class="truncate text-xs text-theme-700 dark:text-zinc-400"
                    >
                      {{ courseClass.teacherName }}
                    </div>
                  </div>
                  <Icon
                    name="arrow-top-right-on-square"
                    class="size-4 shrink-0 text-theme-700 transition group-hover:text-theme-800 dark:text-zinc-400 dark:group-hover:text-zinc-300"
                  />
                </a>
                <div
                  v-else
                  class="min-w-0 flex-1 px-3 py-2 text-theme-700 dark:text-zinc-400"
                >
                  <div class="font-semibold tabular-nums">
                    {{ courseClass.code }}
                  </div>
                  <div v-if="courseClass.teacherName" class="truncate text-xs">
                    {{ courseClass.teacherName }}
                  </div>
                </div>

                <a
                  v-if="courseClass.backupClassroomUrl"
                  :href="courseClass.backupClassroomUrl"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="flex shrink-0 items-center gap-1 border-l border-theme-200 px-2.5 text-xs font-medium text-theme-700 transition hover:bg-theme-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
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
  </section>
</template>
