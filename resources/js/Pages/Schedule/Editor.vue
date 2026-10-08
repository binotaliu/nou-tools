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

// 專班: each entry's courses carry exactly that 專班's class, so a saved item
// whose class belongs to one is resolved from here rather than from the
// 一般生 course list (專班-only courses are not in it).
const programs = computed(() => props.viewModel.programs ?? [])

const programCourseByClassId = computed(
  () =>
    new Map(
      programs.value.flatMap(program =>
        program.courses.map(course => [course.classes[0].id, course])
      )
    )
)

function initialSelection() {
  return (props.viewModel.selectedItems ?? []).flatMap(item => {
    const fullCourse =
      programCourseByClassId.value.get(item.classId) ??
      allCourses.value.find(c => c.id === item.courseId)

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

const selectedRegion = ref('')
const selectedProgramId = ref('')
const programError = ref('')

const regions = computed(() => {
  const seen = new Map()

  programs.value.forEach(program => {
    if (!seen.has(program.region)) {
      seen.set(program.region, program.region_label)
    }
  })

  return [...seen].map(([region, label]) => ({ region, label }))
})

const programsInRegion = computed(() =>
  programs.value.filter(program => program.region === selectedRegion.value)
)

watch(selectedRegion, () => {
  selectedProgramId.value = ''
  programError.value = ''
})

function isProgramItem(item) {
  return item.course.classes.every(c => c.type === 'special_program')
}

// Adds every course of the chosen 專班. A course already picked (e.g. as a
// 一般課程) switches to the 專班's class instead of being added twice.
function addProgram() {
  const program = programs.value.find(
    p => String(p.id) === selectedProgramId.value
  )

  if (!program) {
    return
  }

  const added = program.courses.filter(
    course => !selectedIds.value.includes(course.id)
  )

  if (selectedItems.value.length + added.length > MAX_COURSES) {
    programError.value = `加入後會超過 ${MAX_COURSES} 門課程的上限，請先移除部分課程。`
    return
  }

  programError.value = ''

  program.courses.forEach(course => {
    const entry = { course, selectedClassId: course.classes[0].id }
    const index = selectedItems.value.findIndex(i => i.course.id === course.id)

    if (index === -1) {
      selectedItems.value.push(entry)
    } else {
      selectedItems.value.splice(index, 1, entry)
    }
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

// Back to step 1 for another course, with the search and filters cleared so
// the full list is showing again.
function addAnotherCourse() {
  filters.clearFilters()
  filters.groupBy = 'exam'
  goToStep(1)
}

// A visitor who already has a schedule is asked first whether they mean to add
// to it or start over; "start over" just reveals the normal create flow.
const startedFresh = ref(false)
const needsChoice = computed(
  () =>
    !editing.value && !!props.viewModel.previousSchedule && !startedFresh.value
)

function goToStep(target) {
  step.value = target
  stepError.value = ''

  nextTick(() => {
    stepHeading.value?.focus()
    window.scrollTo({ top: 0 })
  })
}

// Switching through the step radios keeps focus on the radio so arrow keys
// keep working; the buttons move focus to the new step's heading instead.
function switchStep(target) {
  step.value = target
  stepError.value = ''
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
    selectedRegion.value = ''
    programError.value = ''
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

      <section
        v-if="needsChoice"
        data-testid="schedule-existing-choice"
        class="mb-6 rounded-lg border border-theme-200 bg-theme-50 p-6 dark:border-zinc-700 dark:bg-zinc-950"
      >
        <h3
          class="mb-1 text-xl font-semibold text-theme-900 dark:text-zinc-100"
        >
          你已經有課表，你想新增課程到課表，還是要建立新課表呢？
        </h3>
        <p class="mb-4 text-sm text-theme-700 dark:text-zinc-400">
          目前的課表：{{ viewModel.previousSchedule.name || '（未命名）' }}
        </p>
        <div class="flex flex-col gap-3 sm:flex-row">
          <Link
            :href="`/schedules/${viewModel.previousSchedule.token}/edit`"
            data-testid="schedule-choice-add"
            data-analytics-event="schedule_choice_add"
            data-analytics-feature="schedule"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-6 py-3 text-lg font-semibold text-white transition hover:bg-theme-800"
          >
            新增課程到我的課表
          </Link>
          <button
            type="button"
            data-testid="schedule-choice-restart"
            data-analytics-event="schedule_choice_restart"
            data-analytics-feature="schedule"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-6 py-3 text-lg font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
            @click="startedFresh = true"
          >
            刪除我的課程，重新開始
          </button>
        </div>
      </section>

      <template v-if="!needsChoice">
        <fieldset class="mb-6" data-testid="schedule-steps">
          <legend class="sr-only">步驟</legend>
          <div class="flex flex-wrap gap-3">
            <label
              v-for="item in steps"
              :key="item.number"
              :data-testid="'schedule-step-label-' + item.number"
              class="flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-semibold focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-theme-700"
              :class="[
                step === item.number
                  ? 'border-theme-700 bg-theme-700 text-white'
                  : 'border-theme-300 text-theme-800 dark:border-zinc-600 dark:text-zinc-200',
                item.number === 2 && selectedItems.length === 0
                  ? 'cursor-not-allowed opacity-60'
                  : 'cursor-pointer',
              ]"
            >
              <input
                type="radio"
                name="schedule-step"
                class="sr-only"
                :value="item.number"
                :data-testid="'schedule-step-' + item.number"
                :checked="step === item.number"
                :disabled="item.number === 2 && selectedItems.length === 0"
                @change="switchStep(item.number)"
              />
              <span class="tabular-nums">{{ item.number }}</span>
              {{ item.label }}
            </label>
          </div>
        </fieldset>

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
            請選擇本學期課程（最多 {{ MAX_COURSES }} 門）。
          </p>

          <section
            v-if="programs.length > 0"
            data-testid="program-picker"
            aria-labelledby="program-picker-heading"
            class="mb-6 rounded-lg border border-theme-200 bg-theme-50 p-4 dark:border-zinc-700 dark:bg-zinc-950"
          >
            <h4
              id="program-picker-heading"
              class="mb-3 text-lg font-semibold text-theme-900 dark:text-zinc-100"
            >
              加入專班課程
            </h4>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
              <div class="flex-1">
                <label
                  for="program-region"
                  class="mb-1 block text-sm font-semibold text-theme-800 dark:text-zinc-200"
                >
                  地區
                </label>
                <Select
                  id="program-region"
                  v-model="selectedRegion"
                  data-testid="program-region"
                >
                  <option value="">請選擇地區</option>
                  <option
                    v-for="item in regions"
                    :key="item.region"
                    :value="item.region"
                  >
                    {{ item.label }}
                  </option>
                </Select>
              </div>
              <div class="flex-1">
                <label
                  for="program-name"
                  class="mb-1 block text-sm font-semibold text-theme-800 dark:text-zinc-200"
                >
                  專班
                </label>
                <Select
                  id="program-name"
                  v-model="selectedProgramId"
                  :disabled="selectedRegion === ''"
                  data-testid="program-name"
                >
                  <option value="">請選擇專班</option>
                  <option
                    v-for="program in programsInRegion"
                    :key="program.id"
                    :value="String(program.id)"
                  >
                    {{ program.name }}
                  </option>
                </Select>
              </div>
              <button
                type="button"
                data-testid="program-add"
                :disabled="selectedProgramId === ''"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-5 py-2 font-semibold text-white transition hover:bg-theme-800 disabled:bg-theme-400"
                @click="addProgram"
              >
                加入
              </button>
            </div>
            <p
              v-if="programError"
              role="alert"
              data-testid="program-error"
              class="mt-3 text-sm font-semibold text-red-700 dark:text-red-400"
            >
              {{ programError }}
            </p>
          </section>

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

              <div
                v-else-if="isProgramItem(item)"
                data-testid="program-class-note"
                class="rounded-lg border-2 border-theme-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900"
              >
                <div class="font-semibold text-theme-900 dark:text-zinc-100">
                  專班：{{ item.course.classes[0].code }}
                </div>
                <div
                  v-if="item.course.classes[0].start_time"
                  class="text-sm text-theme-700 dark:text-zinc-400"
                >
                  {{ item.course.classes[0].start_time }} -
                  {{ item.course.classes[0].end_time }}
                </div>
                <div
                  v-if="item.course.classes[0].teacher_name"
                  class="text-sm text-theme-700 dark:text-zinc-400"
                >
                  {{ item.course.classes[0].teacher_name }}
                </div>
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
                上一步
              </button>
              <button
                v-if="!limitReached"
                type="button"
                data-testid="schedule-add-more"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-6 py-3 text-lg font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
                @click="addAnotherCourse"
              >
                新增其他課程
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
      </template>
    </div>
  </AppLayout>
</template>
