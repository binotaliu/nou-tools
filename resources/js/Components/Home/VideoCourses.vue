<script setup>
// Homepage 今日視訊面授: the courses with a video in-person class on the
// selected day. Each course is one row group; inside it every period (上午班,
// 夜間班, …) gets a row with its time on the left and the class chips on the
// right, so there are no boxes nested in boxes. The date filter is driven by
// useDatePicker.js and navigates with ?date=.
import { computed, ref } from 'vue'
import Icon from '../Icon.vue'
import DateField from '../DateField.vue'
import useDatePicker from '../../Composables/useDatePicker'
import { localTimeHint } from '../../Composables/useLocalTimeHint'
import useSessionClock, {
  sessionState,
} from '../../Composables/useSessionClock'

const props = defineProps({
  courses: {
    type: Array,
    required: true,
  },
  selectedDate: {
    type: String,
    required: true,
  },
  // The standalone page carries its own H2, so it hides the card's title.
  showTitle: {
    type: Boolean,
    default: true,
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

const { now } = useSessionClock()

// Ended courses (over more than 30 minutes ago) are hidden unless asked for.
const SHOW_ENDED_KEY = 'nou:video-classes:show-ended:v1'
const showEnded = ref(false)

try {
  showEnded.value = localStorage.getItem(SHOW_ENDED_KEY) === '1'
} catch {
  // Storage can be unavailable; the default (hidden) applies.
}

function setShowEnded(value) {
  showEnded.value = value

  try {
    localStorage.setItem(SHOW_ENDED_KEY, value ? '1' : '0')
  } catch {
    // Not persisted; still applies for this visit.
  }
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
        const [startTime, endTime] = timeLabel.includes(' - ')
          ? timeLabel.split(' - ')
          : [null, null]

        slots.push({
          key: `${key}-${timeLabel}`,
          label,
          timeLabel,
          startTime,
          endTime,
          classes,
        })
      })
    })

    return {
      ...course,
      slots,
      classCount: course.classes.length,
    }
  })
)

const statusBadges = {
  live: {
    label: '上課中',
    classes:
      'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
  },
  soon: {
    label: '即將開始',
    classes:
      'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300',
  },
}

const localHintOf = slot =>
  localTimeHint(props.selectedDate, slot.startTime, slot.endTime)

const stateOf = slot =>
  sessionState(props.selectedDate, slot.startTime, slot.endTime, now.value)

const TABS = [
  { key: 'live', label: '上課中' },
  { key: 'soon', label: '即將開始' },
  { key: 'all', label: '所有教室' },
]

// 即將開始 covers every slot that has not started yet, not just the 30-minute
// "soon" window, so the tab is useful earlier in the day.
const matchesTab = (slot, tab) => {
  const state = stateOf(slot)

  if (tab === 'live') {
    return state === 'live'
  }

  if (tab === 'soon') {
    return state === 'soon' || state === 'upcoming'
  }

  return showEnded.value || state !== 'ended'
}

const hasSlotIn = tab =>
  groupedCourses.value.some(course =>
    course.slots.some(slot => matchesTab(slot, tab))
  )

// Until the viewer picks a tab it follows the clock: live classes first, then
// the ones still to come, then everything.
const pickedTab = ref(null)
const activeTab = computed(
  () =>
    pickedTab.value ??
    (hasSlotIn('live') ? 'live' : hasSlotIn('soon') ? 'soon' : 'all')
)

const visibleCourses = computed(() =>
  groupedCourses.value
    .map(course => {
      const slots = course.slots.filter(slot =>
        matchesTab(slot, activeTab.value)
      )

      return {
        ...course,
        slots,
        classCount: slots.reduce((sum, slot) => sum + slot.classes.length, 0),
      }
    })
    .filter(course => course.slots.length > 0)
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
        <h2
          v-if="showTitle"
          class="text-xl font-semibold text-theme-900 dark:text-zinc-100"
        >
          今日視訊面授
        </h2>
      </div>

      <div class="flex flex-wrap items-center gap-2">
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
      v-if="groupedCourses.length > 0"
      role="tablist"
      aria-label="課程狀態"
      class="mb-4 flex gap-1 rounded-lg bg-theme-100 p-1 dark:bg-zinc-800"
    >
      <button
        v-for="tab in TABS"
        :key="tab.key"
        type="button"
        role="tab"
        :aria-selected="activeTab === tab.key"
        :data-testid="`video-courses-tab-${tab.key}`"
        class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition"
        :class="
          activeTab === tab.key
            ? 'bg-white text-theme-900 shadow-sm dark:bg-zinc-950 dark:text-zinc-100'
            : 'text-theme-700 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-200'
        "
        @click="pickedTab = tab.key"
      >
        {{ tab.label }}
      </button>
    </div>

    <label
      v-if="groupedCourses.length > 0 && activeTab === 'all'"
      class="mb-4 inline-flex cursor-pointer items-center gap-2 text-sm text-theme-700 dark:text-zinc-400"
    >
      <input
        type="checkbox"
        :checked="showEnded"
        class="size-4 rounded border-zinc-500 accent-theme-700"
        data-testid="video-courses-show-ended"
        @change="setShowEnded($event.target.checked)"
      />
      顯示已結束課程
    </label>

    <div
      v-if="groupedCourses.length === 0"
      class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-theme-300 bg-theme-50 px-4 py-12 text-theme-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-400"
    >
      <Icon name="face-smile" class="size-8" />
      <p class="text-lg font-medium">今日無面授課程</p>
    </div>

    <div
      v-else-if="visibleCourses.length === 0"
      class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-theme-300 bg-theme-50 px-4 py-12 text-theme-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-400"
      data-testid="video-courses-all-ended"
    >
      <Icon name="face-smile" class="size-8" />
      <p class="text-lg font-medium">
        {{
          activeTab === 'live'
            ? '目前沒有上課中的課程'
            : activeTab === 'soon'
              ? '沒有即將開始的課程'
              : '已無進行中或未開始的課程'
        }}
      </p>
      <p v-if="activeTab === 'all'" class="text-sm">
        勾選「顯示已結束課程」可檢視已結束的課程。
      </p>
    </div>

    <div v-else class="divide-y divide-theme-100 dark:divide-zinc-800">
      <div
        v-for="course in visibleCourses"
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
            data-testid="video-course-slot"
            class="flex flex-col gap-2 sm:flex-row sm:items-start sm:gap-4"
          >
            <div
              class="flex shrink-0 items-center gap-2 sm:w-36 sm:flex-col sm:items-start sm:gap-1 sm:pt-1.5"
            >
              <span class="flex flex-wrap items-center gap-1">
                <span
                  class="rounded-full bg-theme-100 px-2 py-0.5 text-xs font-medium text-theme-800 dark:bg-zinc-800 dark:text-zinc-200"
                >
                  {{ slot.label }}
                </span>
                <span
                  v-if="statusBadges[stateOf(slot)]"
                  class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                  :class="statusBadges[stateOf(slot)].classes"
                  :data-state="stateOf(slot)"
                  data-testid="video-course-status"
                >
                  <span
                    class="size-1.5 rounded-full bg-current motion-safe:animate-pulse"
                    aria-hidden="true"
                  />
                  {{ statusBadges[stateOf(slot)].label }}
                </span>
              </span>
              <span
                class="inline-flex items-center gap-1 text-sm text-theme-700 tabular-nums dark:text-zinc-400"
              >
                <Icon name="clock" class="size-3.5" />
                {{ slot.timeLabel }}
              </span>
              <span
                v-if="localHintOf(slot)"
                class="text-xs text-theme-700 dark:text-zinc-400"
                data-testid="video-course-local-time"
              >
                {{ localHintOf(slot) }}
              </span>
            </div>

            <div
              class="grid min-w-0 flex-1 grid-cols-2 gap-2 sm:flex sm:flex-wrap"
            >
              <div
                v-for="courseClass in slot.classes"
                :key="courseClass.id"
                class="flex min-w-0 items-stretch overflow-hidden rounded-lg border bg-theme-50 sm:w-auto sm:min-w-44 dark:bg-zinc-950"
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
                  class="group flex min-w-0 flex-1 items-center gap-3 px-3 py-2 transition hover:bg-theme-100 focus-visible:-outline-offset-2 dark:hover:bg-zinc-800"
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
                  class="flex shrink-0 items-center gap-1 border-l border-theme-200 px-2.5 text-xs font-medium text-theme-700 transition hover:bg-theme-100 focus-visible:-outline-offset-2 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
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
