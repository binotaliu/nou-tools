<script setup>
// Serves both the create ('/schedules/create') and edit
// ('/schedules/{schedule}/edit') routes, since
// ScheduleController::create()/edit() share this one view.
//
// Two client-side steps on one page: 1 picks courses (browsed like
// 本學期開課表), 2 picks a class for each and saves.
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Select from '../../Components/Select.vue'
import CourseFilters from '../../Components/Courses/CourseFilters.vue'
import CourseGroupList from '../../Components/Courses/CourseGroupList.vue'
import ClassOptions from '../../Components/Schedule/ClassOptions.vue'
import { toSemesterDisplay } from '../../Composables/semesterDisplay.js'
import { useCourseFilters } from '../../Composables/useCourseFilters.js'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const MAX_COURSES = 14

const editing = computed(() => props.viewModel.scheduleUuid !== null)
const pageTitle = computed(() => (editing.value ? '編輯課表' : '新增課表'))
const headingText = computed(() =>
  editing.value ? '編輯您的課表' : '建立您的課表'
)
const submitLabel = computed(() => (editing.value ? '更新課表' : '建立課表'))
const submittingLabel = computed(() =>
  editing.value ? '更新中...' : '建立中...'
)

const formAction = computed(() =>
  editing.value ? `/schedules/${props.viewModel.scheduleUuid}` : '/schedules'
)

const allCourses = computed(() => props.viewModel.courses)

// Flat rows for the shared course list; `section` mirrors the schedule page
// (courses with an exam slot are 一般課程, the rest 微學分與全遠距).
const courseRows = computed(() =>
  allCourses.value.map(course => ({
    id: course.id,
    name: course.name,
    department: course.department,
    credits: course.credits,
    section: course.exam_label ? 'general' : 'micro',
    examLabel: course.exam_label,
    examWeekdayOrder: course.exam_weekday_order,
    examTimeStart: course.exam_time_start,
  }))
)

const filters = reactive(useCourseFilters(courseRows))

const scheduleName = ref(props.viewModel.scheduleName ?? '')

function initialSelection() {
  return (props.viewModel.selectedItems ?? []).flatMap(item => {
    const fullCourse = allCourses.value.find(c => c.id === item.courseId)

    return fullCourse
      ? [{ course: fullCourse, selectedClassId: item.classId }]
      : []
  })
}

const selectedItems = ref(initialSelection())
const step = ref(editing.value && selectedItems.value.length > 0 ? 2 : 1)
const stepError = ref('')

const selectedIds = computed(() => selectedItems.value.map(i => i.course.id))
const limitReached = computed(() => selectedItems.value.length >= MAX_COURSES)

function toggleCourse(row) {
  const index = selectedItems.value.findIndex(i => i.course.id === row.id)

  if (index !== -1) {
    selectedItems.value.splice(index, 1)
    return
  }

  if (limitReached.value) {
    return
  }

  const course = allCourses.value.find(c => c.id === row.id)

  selectedItems.value.push({
    course,
    selectedClassId: course.classes.length > 0 ? course.classes[0].id : null,
  })
}

function removeItem(index) {
  selectedItems.value.splice(index, 1)
  stepError.value = ''

  if (selectedItems.value.length === 0) {
    goToStep(1)
  }
}

const stepHeading = ref(null)

function goToStep(target) {
  step.value = target
  stepError.value = ''

  nextTick(() => {
    stepHeading.value?.focus()
    window.scrollTo({ top: 0 })
  })
}

function selectTerm(term) {
  const url = editing.value
    ? `/schedules/${props.viewModel.scheduleUuid}/edit`
    : '/schedules/create'

  router.get(url, { term }, { preserveScroll: true })
}

// Inertia reuses this component when only the term changes, so the picks
// belong to the old term's courses and must be rebuilt.
watch(
  () => props.viewModel.selectedTerm,
  () => {
    selectedItems.value = initialSelection()
    step.value = editing.value && selectedItems.value.length > 0 ? 2 : 1
    stepError.value = ''
  }
)

const form = useForm({})

function submitForm() {
  if (selectedItems.value.length === 0) {
    stepError.value = '請至少選擇一門課程'
    return
  }

  const missing = selectedItems.value.filter(
    item => item.course.has_classes && !item.selectedClassId
  )

  if (missing.length > 0) {
    stepError.value = `請為所有課程選擇班級：${missing.map(i => i.course.name).join('、')}`
    return
  }

  form
    .transform(() => ({
      term: props.viewModel.selectedTerm,
      name: scheduleName.value,
      items: selectedItems.value.map(item => ({
        course_id: item.course.id,
        ...(item.selectedClassId ? { class_id: item.selectedClassId } : {}),
      })),
    }))
    .submit(editing.value ? 'put' : 'post', formAction.value)
}

const steps = [
  { number: 1, label: '選擇課程' },
  { number: 2, label: '選擇班級' },
]
</script>

<template>
  <Head :title="`${pageTitle} - NOU 小幫手`" />

  <AppLayout>
    <div class="mx-auto max-w-5xl">
      <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          {{ headingText }}
        </h2>
        <div class="w-full sm:w-auto sm:min-w-40">
          <Select
            id="term"
            name="term"
            aria-label="選擇學期"
            :model-value="viewModel.selectedTerm"
            @update:model-value="selectTerm"
          >
            <option
              v-for="term in viewModel.availableTerms"
              :key="term"
              :value="term"
            >
              {{ toSemesterDisplay(term) }}
            </option>
          </Select>
        </div>
      </div>

      <div
        v-if="viewModel.previousSchedule && !editing"
        class="mb-6 flex items-center justify-between gap-3 rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-900 dark:border-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-200"
      >
        <div>
          <div class="font-medium">
            你曾建立過課表：
            <span class="text-theme-900 dark:text-zinc-100">
              {{ viewModel.previousSchedule.name || '（未命名）' }}
            </span>
            ，確定要繼續新增新課表嗎？
          </div>
        </div>
        <div class="flex gap-2">
          <Link
            :href="`/schedules/${viewModel.previousSchedule.token}`"
            class="rounded bg-yellow-400 px-4 py-2 font-semibold text-yellow-900 hover:bg-yellow-500 dark:bg-yellow-600 dark:text-yellow-950 dark:hover:bg-yellow-500"
            data-analytics-event="schedule_open_previous"
            data-analytics-feature="schedule"
          >
            檢視舊課表
          </Link>
        </div>
      </div>

      <ol class="mb-6 flex flex-wrap gap-3" data-testid="schedule-steps">
        <li
          v-for="item in steps"
          :key="item.number"
          :aria-current="step === item.number ? 'step' : null"
          class="flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-semibold"
          :class="
            step === item.number
              ? 'border-theme-700 bg-theme-700 text-white'
              : 'border-theme-300 text-theme-800 dark:border-zinc-600 dark:text-zinc-200'
          "
        >
          <span class="tabular-nums">{{ item.number }}</span>
          {{ item.label }}
        </li>
      </ol>

      <!-- Step 1: courses -->
      <div v-if="step === 1" data-testid="schedule-step-courses">
        <h3
          ref="stepHeading"
          tabindex="-1"
          class="mb-1 text-xl font-semibold text-theme-900 focus:outline-none dark:text-zinc-100"
        >
          選擇課程
        </h3>
        <p class="mb-4 text-sm text-theme-700 dark:text-zinc-400">
          勾選這學期要修的課程（最多 {{ MAX_COURSES }} 門），下一步再選擇班級。
        </p>

        <CourseFilters :filters="filters" />

        <CourseGroupList
          :sections="filters.sections"
          :columns="filters.columns"
          selectable
          :selected-ids="selectedIds"
          :limit-reached="limitReached"
          @toggle="toggleCourse"
        />

        <div
          class="sticky bottom-(--pwa-nav-height,0px) z-10 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-theme-200 bg-white p-4 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
        >
          <p
            class="text-sm text-theme-800 dark:text-zinc-200"
            data-testid="selected-count"
            aria-live="polite"
          >
            已選 {{ selectedItems.length }} / {{ MAX_COURSES }} 門課程
            <span v-if="limitReached">，已達上限，請先取消勾選再新增。</span>
          </p>
          <button
            type="button"
            data-testid="schedule-next"
            :disabled="selectedItems.length === 0"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-6 py-2 font-semibold text-white transition hover:bg-theme-800 disabled:bg-theme-400"
            @click="goToStep(2)"
          >
            下一步：選擇班級
          </button>
        </div>
      </div>

      <!-- Step 2: classes -->
      <div v-else data-testid="schedule-step-classes">
        <h3
          ref="stepHeading"
          tabindex="-1"
          class="mb-1 text-xl font-semibold text-theme-900 focus:outline-none dark:text-zinc-100"
        >
          選擇班級
        </h3>
        <p class="mb-4 text-sm text-theme-700 dark:text-zinc-400">
          為每門課程選擇要上的班級。
        </p>

        <div class="mb-8 space-y-4">
          <div
            v-for="(item, index) in selectedItems"
            :key="item.course.id"
            :data-testid="'selected-item-' + item.course.id"
            class="rounded-lg border-2 border-theme-300 bg-theme-50 p-4 dark:border-zinc-600 dark:bg-zinc-950"
          >
            <div class="mb-3 flex items-start justify-between gap-3">
              <h4 class="text-lg font-bold text-theme-900 dark:text-zinc-100">
                {{ item.course.name }}
              </h4>
              <button
                type="button"
                :aria-label="`移除 ${item.course.name}`"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-100 bg-red-100 px-3 py-1 text-sm font-semibold text-red-700 transition hover:bg-red-200"
                @click="removeItem(index)"
              >
                移除
              </button>
            </div>

            <div
              v-if="!item.course.has_classes"
              data-testid="pending-class-note"
              class="rounded-lg border-2 border-dashed border-theme-300 bg-theme-100 p-3 text-sm text-theme-700 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-300"
            >
              尚未開課，選課後將自動列入課表，開課後請記得回來選擇班級。
            </div>

            <ClassOptions
              v-else
              v-model="item.selectedClassId"
              :course="item.course"
              :name="'class_' + index"
            />
          </div>
        </div>

        <form
          class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
          @submit.prevent="submitForm"
        >
          <div class="mb-4">
            <label
              class="mb-1 block text-xl font-semibold text-theme-900 dark:text-zinc-100"
              for="schedule-name"
            >
              課表名稱（可選）
            </label>
            <input
              id="schedule-name"
              v-model="scheduleName"
              type="text"
              name="name"
              placeholder="例如：浣熊的課表"
              class="w-full rounded-lg border-2 border-zinc-500 px-4 py-3 focus:border-theme-500 focus:ring-2 focus:ring-theme-500 focus:outline-none dark:border-zinc-500"
            />
          </div>

          <p
            v-if="stepError"
            role="alert"
            data-testid="schedule-error"
            class="mb-4 text-sm font-semibold text-red-700 dark:text-red-400"
          >
            {{ stepError }}
          </p>

          <div class="flex flex-col gap-3 sm:flex-row">
            <button
              type="button"
              data-testid="schedule-back"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-6 py-3 text-lg font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
              @click="goToStep(1)"
            >
              {{ editing ? '調整課程' : '上一步' }}
            </button>
            <button
              type="submit"
              data-testid="schedule-submit"
              :disabled="selectedItems.length === 0 || form.processing"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-6 py-3 text-lg font-semibold text-white transition hover:bg-theme-800 disabled:bg-theme-400"
            >
              <span v-if="!form.processing">{{ submitLabel }}</span>
              <span v-else>{{ submittingLabel }}</span>
            </button>
            <Link
              :href="
                editing
                  ? `/schedules/${viewModel.scheduleUuid}`
                  : '/schedules/create'
              "
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-6 py-3 text-lg font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
            >
              取消
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
